<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  if ( !class_exists('PayPal') ) {
    include DIR_FS_CATALOG . 'includes/apps/paypal/PayPal.php';
  }

  class paypal_pro_hs {

    const REQUIRES = [
      'firstname',
      'lastname',
      'street_address',
      'city',
      'postcode',
      'country',
      'telephone',
      'email_address',
    ];

    public $code = 'paypal_pro_hs';
    public $title, $description, $enabled;
    public $_app;

    public function __construct() {
      global $order;

      $this->_app = new PayPal();
      $this->_app->loadLanguageFile('modules/HS/HS.php');

      $this->signature = 'paypal|paypal_pro_hs|' . $this->_app->getVersion() . '|2.3';
      $this->api_version = $this->_app->getApiVersion();

      $this->title = $this->_app->getDef('module_hs_title');
      $this->public_title = $this->_app->getDef('module_hs_public_title');
      $link = isset($GLOBALS['Admin']) ? $GLOBALS['Admin']->link('paypal.php', 'action=configure&module=HS') : '';
      $this->description = '<div align="center">' . $this->_app->drawButton($this->_app->getDef('module_hs_legacy_admin_app_button'), $link, 'primary', null, true) . '</div>';
      $this->sort_order = defined('PAYPAL_APP_HS_SORT_ORDER') ? PAYPAL_APP_HS_SORT_ORDER : 0;
      $this->enabled = defined('PAYPAL_APP_HS_STATUS') && in_array(PAYPAL_APP_HS_STATUS, ['1', '0']);
      $this->order_status = defined('PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID') && ((int)PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID > 0) ? (int)PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID : 0;

      if ( defined('PAYPAL_APP_HS_STATUS') ) {
        if ( PAYPAL_APP_HS_STATUS == '0' ) {
          $this->title .= ' [Sandbox]';
          $this->public_title .= ' (' . $this->code . '; Sandbox)';
        }

        if ( PAYPAL_APP_HS_STATUS == '1' ) {
          $this->api_url = 'https://api-3t.paypal.com/nvp';
        } else {
          $this->api_url = 'https://api-3t.sandbox.paypal.com/nvp';
        }
      }

      if ( !function_exists('curl_init') ) {
        $this->description .= '<div class="alert alert-warning">' . $this->_app->getDef('module_hs_error_curl') . '</div>';

        $this->enabled = false;
      }

      if ( $this->enabled === true ) {
        if ( (PAYPAL_APP_GATEWAY == '1') && !$this->_app->hasCredentials('HS') ) { // PayPal
          $this->description .= '<div class="alert alert-warning">' . $this->_app->getDef('module_hs_error_credentials') . '</div>';

          $this->enabled = false;
        } elseif ( PAYPAL_APP_GATEWAY == '0' ) { // Payflow
          $this->description .= '<div class="alert alert-warning">' . $this->_app->getDef('module_hs_error_payflow') . '</div>';

          $this->enabled = false;
        }
      }

      if ( $this->enabled === true ) {
        if ( isset($order->billing) ) {
          $this->update_status();
        }
      }
    }

    public function update_status() {
      global $order;

      if ( $this->enabled && ((int)PAYPAL_APP_HS_ZONE > 0) ) {
        $check_query = $GLOBALS['db']->query("SELECT zone_id FROM zones_to_geo_zones WHERE geo_zone_id = '" . (int)PAYPAL_APP_HS_ZONE . "' AND zone_country_id = '" . (int)$GLOBALS['customer_data']->get('country_id', $order->billing) . "' ORDER BY zone_id");
        while ($check = $check_query->fetch_assoc()) {
          if (($check['zone_id'] < 1) || ($check['zone_id'] === $GLOBALS['customer_data']->get('zone_id', $order->billing))) {
            return;
          }
        }

        $this->enabled = false;
      }
    }

    public function javascript_validation() {
      return false;
    }

    private function extract_order_id() {
      return substr($_SESSION['cart_PayPal_Pro_HS_ID'], strpos($_SESSION['cart_PayPal_Pro_HS_ID'], '-')+1);
    }

    public function selection() {
      if (isset($_SESSION['cart_PayPal_Pro_HS_ID'])) {
        $order_id = $this->extract_order_id();

        $check_query = $GLOBALS['db']->query('SELECT orders_id FROM orders_status_history WHERE orders_id = ' . (int)$order_id . ' LIMIT 1');

        if (mysqli_num_rows($check_query) < 1) {
          order::remove($order_id);
          unset($_SESSION['cart_PayPal_Pro_HS_ID']);
        }
      }

      return [
        'id' => $this->code,
        'module' => $this->public_title,
      ];
    }

    public function pre_confirmation_check() {
      if (empty($_SESSION['cart']->cartID)) {
        $_SESSION['cartID'] = $_SESSION['cart']->cartID = $_SESSION['cart']->generate_cart_id();
      }
    }

    public function confirmation() {
      global $order, $order_total_modules, $customer_data;

      $_SESSION['pphs_result'] = [];

      if (isset($_SESSION['cartID'])) {
        $insert_order = false;

        if (isset($_SESSION['cart_PayPal_Pro_HS_ID'])) {
          $order_id = $this->extract_order_id();

          $curr = $GLOBALS['db']->query("SELECT currency FROM orders WHERE orders_id = " . (int)$order_id)->fetch_assoc();

          if ( ($curr['currency'] != $order->info['currency'])
            || ($_SESSION['cartID'] != substr($_SESSION['cart_PayPal_Pro_HS_ID'], 0, strlen($_SESSION['cartID'])))
             )
          {
            $check_query = $GLOBALS['db']->query('SELECT orders_id FROM orders_status_history WHERE orders_id = ' . (int)$order_id . ' LIMIT 1');

            if (mysqli_num_rows($check_query) < 1) {
              order::remove($order_id);
            }

            $insert_order = true;
          }
        } else {
          $insert_order = true;
        }

        if ($insert_order) {
          require 'includes/system/segments/checkout/build_order_totals.php';
          require 'includes/system/segments/checkout/insert_order.php';

          $_SESSION['cart_PayPal_Pro_HS_ID'] = $_SESSION['cartID'] . '-' . $order_id;
        } else {
          $order_id = $this->extract_order_id();
        }

        $params = [
          'buyer_email' => $customer_data->get('email_address', $order->customer),
          'cancel_return' => $GLOBALS['Linker']->build('checkout_payment.php'),
          'currency_code' => $_SESSION['currency'],
          'invoice' => $order_id,
          'custom' => $_SESSION['customer_id'],
          'paymentaction' => PAYPAL_APP_HS_TRANSACTION_METHOD == '1' ? 'sale' : 'authorization',
          'return' => $GLOBALS['Linker']->build('checkout_process.php'),
          'notify_url' => $GLOBALS['Linker']->build('ext/modules/payment/paypal/pro_hosted_ipn.php', '', 'SSL', false, false),
          'shipping' => $GLOBALS['currencies']->format_raw($order->info['shipping_cost']),
          'tax' => $GLOBALS['currencies']->format_raw($order->info['tax']),
          'subtotal' => $GLOBALS['currencies']->format_raw($order->info['total'] - $order->info['shipping_cost'] - $order->info['tax']),
          'billing_first_name' => $customer_data->get('firstname', $order->billing),
          'billing_last_name' => $customer_data->get('lastname', $order->billing),
          'billing_address1' => $customer_data->get('street_address', $order->billing),
          'billing_address2' => $customer_data->get('suburb', $order->billing),
          'billing_city' => $customer_data->get('city', $order->billing),
          'billing_state' => Zone::fetch_code(
            $customer_data->get('country_id', $order->billing),
            $customer_data->get('zone_id', $order->billing),
            $customer_data->get('state', $order->billing)),
          'billing_zip' => $customer_data->get('postcode', $order->billing),
          'billing_country' => $customer_data->get('country_iso_code_2', $order->billing),
          'night_phone_b' => $customer_data->get('telephone', $order->customer),
          'template' => 'templateD',
          'item_name' => STORE_NAME,
          'showBillingAddress' => 'false',
          'showShippingAddress' => 'false',
          'showHostedThankyouPage' => 'false',
        ];

        if ( is_numeric($_SESSION['sendto']) && ($_SESSION['sendto'] > 0) ) {
          $params['address_override'] = 'true';
          $customer_data->get('country', $order->delivery);
          $params['first_name'] = $customer_data->get('firstname', $order->delivery);
          $params['last_name'] = $customer_data->get('lastname', $order->delivery);
          $params['address1'] = $customer_data->get('street_address', $order->delivery);
          $params['address2'] = $customer_data->get('suburb', $order->delivery);
          $params['city'] = $customer_data->get('city', $order->delivery);
          $params['state'] = Zone::fetch_code(
            $customer_data->get('country_id', $order->delivery),
            $customer_data->get('zone_id', $order->delivery),
            $customer_data->get('state', $order->delivery));
          $params['zip'] = $customer_data->get('postcode', $order->delivery);
          $params['country'] = $customer_data->get('country_iso_code_2', $order->delivery);
        }

        $return_link_title = $this->_app->getDef('module_hs_button_return_to_store', ['storename' => STORE_NAME]);

        if ( strlen($return_link_title) <= 60 ) {
          $params['cbt'] = $return_link_title;
        }

        $_SESSION['pphs_result'] = $this->_app->getApiResult('APP', 'BMCreateButton', $params, (PAYPAL_APP_HS_STATUS == '1') ? 'live' : 'sandbox');
      }

      $_SESSION['pphs_key'] = Password::create_random(16);

      $iframe_url = $GLOBALS['Linker']->build('ext/modules/payment/paypal/hosted_checkout.php', 'key=' . $_SESSION['pphs_key']);
      $form_url = $GLOBALS['Linker']->build('checkout_payment.php', 'payment_error=paypal_pro_hs');

// include jquery if it doesn't exist in the template
      $output = <<<"EOD"
<iframe src="{$iframe_url}" width="570px" height="540px" frameBorder="0" scrolling="no"></iframe>
<script>
if ( typeof jQuery == 'undefined' ) {
  document.write('<scr' + 'ipt src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></scr' + 'ipt>');
}
</script>

<script>
$(function() {
  $('form[name="checkout_confirmation"] input[type="submit"], form[name="checkout_confirmation"] input[type="image"], form[name="checkout_confirmation"] button[type="submit"]').hide();
  $('form[name="checkout_confirmation"]').attr('action', '{$form_url}');
});
</script>

EOD;

      $confirmation = ['title' => $output];

      return $confirmation;
    }

    public function process_button() {
      return false;
    }

    public function before_process() {
      $result = false;

      if ( !empty($_GET['tx']) ) { // direct payment (eg, credit card)
        $result = $this->_app->getApiResult('APP', 'GetTransactionDetails', ['TRANSACTIONID' => $_GET['tx']], (PAYPAL_APP_HS_STATUS == '1') ? 'live' : 'sandbox');
      } elseif ( !empty($_POST['txn_id']) ) { // paypal payment
        $result = $this->_app->getApiResult('APP', 'GetTransactionDetails', ['TRANSACTIONID' => $_POST['txn_id']], (PAYPAL_APP_HS_STATUS == '1') ? 'live' : 'sandbox');
      }

      if ( !in_array($result['ACK'], ['Success', 'SuccessWithWarning']) ) {
        Href::redirect($GLOBALS['Linker']->build('shopping_cart.php', 'error_message=' . stripslashes($result['L_LONGMESSAGE0'])));
      }

      $order = new order($this->extract_order_id());

      $seller_accounts = [$this->_app->getCredentials('HS', 'email')];

      if ( !Text::is_empty($this->_app->getCredentials('HS', 'email_primary')) ) {
        $seller_accounts[] = $this->_app->getCredentials('HS', 'email_primary');
      }

      if ( !isset($result['RECEIVERBUSINESS']) || !in_array($result['RECEIVERBUSINESS'], $seller_accounts) || ($result['INVNUM'] != $order->get_id()) || ($result['CUSTOM'] != $_SESSION['customer_id']) ) {
        Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));
      }

      $_SESSION['pphs_result'] = $result;

      $check_query = $GLOBALS['db']->query("SELECT orders_status FROM orders WHERE orders_id = " . (int)$order->get_id() . " and customers_id = " . (int)$_SESSION['customer_id']);

      if (!mysqli_num_rows($check_query)) {
        Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));
      }

      $check = $check_query->fetch_assoc();

      $this->verifyTransaction($_SESSION['pphs_result']);

      $order->info['order_status'] = DEFAULT_ORDERS_STATUS_ID;

      if ( $check['orders_status'] != PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID ) {
        $order->info['order_status'] = $check['orders_status'];
      }

      if ( (PAYPAL_APP_HS_ORDER_STATUS_ID > 0) && ($check['orders_status'] == PAYPAL_APP_HS_ORDER_STATUS_ID) ) {
        $order->info['order_status'] = PAYPAL_APP_HS_ORDER_STATUS_ID;
      }

      $GLOBALS['db']->query("UPDATE orders SET orders_status = " . (int)$order->info['order_status'] . ", last_modified = NOW() WHERE orders_id = " . (int)$order->get_id());

      $GLOBALS['hooks']->register_pipeline('after');
      require 'includes/system/segments/checkout/insert_history.php';

// load the after_process function from the payment modules
      $this->after_process();

      $GLOBALS['hooks']->register_pipeline('reset');

      unset($_SESSION['cart_PayPal_Pro_HS_ID']);
      unset($_SESSION['pphs_result']);
      unset($_SESSION['pphs_key']);

      Href::redirect($GLOBALS['Linker']->build('checkout_success.php'));
    }

    public function after_process() {
      return false;
    }

    public function get_error() {
      $error = [
        'title' => $this->_app->getDef('module_hs_error_general_title'),
        'error' => $this->_app->getDef('module_hs_error_general'),
      ];

      if ( isset($_SESSION['pphs_error_msg']) ) {
        $error['error'] = $_SESSION['pphs_error_msg'];

        unset($_SESSION['pphs_error_msg']);
      }

      return $error;
    }

    public function check() {
      $check_query = $GLOBALS['db']->query("SELECT configuration_value FROM configuration WHERE configuration_key = 'PAYPAL_APP_HS_STATUS'");
      if ( mysqli_num_rows($check_query) ) {
        $check = $check_query->fetch_assoc();

        return !Text::is_empty($check['configuration_value']);
      }

      return false;
    }

    public function install() {
      Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', 'action=configure&subaction=install&module=HS'));
    }

    public function remove() {
      Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', 'action=configure&subaction=uninstall&module=HS'));
    }

    public function keys() {
      return ['PAYPAL_APP_HS_SORT_ORDER'];
    }

    public function verifyTransaction($pphs_result, $is_ipn = false) {
      $tx_order_id = $pphs_result['INVNUM'];
      $tx_customer_id = $pphs_result['CUSTOM'];
      $tx_transaction_id = $pphs_result['TRANSACTIONID'];
      $tx_payment_status = $pphs_result['PAYMENTSTATUS'];
      $tx_payment_type = $pphs_result['PAYMENTTYPE'];
      $tx_payer_status = $pphs_result['PAYERSTATUS'];
      $tx_address_status = $pphs_result['ADDRESSSTATUS'];
      $tx_amount = $pphs_result['AMT'];
      $tx_currency = $pphs_result['CURRENCYCODE'];
      $tx_pending_reason = $pphs_result['PENDINGREASON'] ?? null;

      if ( is_numeric($tx_order_id) && ($tx_order_id > 0) && is_numeric($tx_customer_id) && ($tx_customer_id > 0) ) {
        $order_query = $GLOBALS['db']->query("SELECT orders_id, orders_status, currency, currency_value FROM orders WHERE orders_id = " . (int)$tx_order_id . " and customers_id = " . (int)$tx_customer_id);

        if ( mysqli_num_rows($order_query) === 1 ) {
          $order = $order_query->fetch_assoc();

          $new_order_status = DEFAULT_ORDERS_STATUS_ID;

          if ( $order['orders_status'] != PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID ) {
            $new_order_status = $order['orders_status'];
          }

          $total = $GLOBALS['db']->query("SELECT value FROM orders_total WHERE orders_id = " . (int)$order['orders_id'] . " and class = 'ot_total' limit 1")->fetch_assoc();

          $comment_status = 'Transaction ID: ' . htmlspecialchars($tx_transaction_id) . "\n"
                          . 'Payer Status: ' . htmlspecialchars($tx_payer_status) . "\n"
                          . 'Address Status: ' . htmlspecialchars($tx_address_status) . "\n"
                          . 'Payment Status: ' . htmlspecialchars($tx_payment_status) . "\n"
                          . 'Payment Type: ' . htmlspecialchars($tx_payment_type) . "\n"
                          . 'Pending Reason: ' . htmlspecialchars($tx_pending_reason);

          if ( $tx_amount != $GLOBALS['currencies']->format_raw($total['value'], $order['currency'], $order['currency_value']) ) {
            $comment_status .= "\n" . 'Error Total Mismatch: PayPal transaction value (' . htmlspecialchars($tx_amount) . ') does not match order value (' . $GLOBALS['currencies']->format_raw($total['value'], $order['currency'], $order['currency_value']) . ')';
          } elseif ( $tx_payment_status == 'Completed' ) {
            $new_order_status = (PAYPAL_APP_HS_ORDER_STATUS_ID > 0 ? PAYPAL_APP_HS_ORDER_STATUS_ID : $new_order_status);
          }

          $GLOBALS['db']->query("UPDATE orders SET orders_status = " . (int)$new_order_status . ", last_modified = NOW() WHERE orders_id = " . (int)$order['orders_id']);

          if ( $is_ipn === true ) {
            $comment_status .= "\n" . 'Source: IPN';
          }

          $sql_data = [
            'orders_id' => (int)$order['orders_id'],
            'orders_status_id' => PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID,
            'date_added' => 'NOW()',
            'customer_notified' => '0',
            'comments' => $comment_status,
          ];

          $GLOBALS['db']->perform('orders_status_history', $sql_data);
        }
      }
    }
  }
