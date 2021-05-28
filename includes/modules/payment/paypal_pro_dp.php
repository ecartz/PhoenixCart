<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  if ( !class_exists('PayPal') ) {
    include(DIR_FS_CATALOG . 'includes/apps/paypal/PayPal.php');
  }

  class paypal_pro_dp {

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

    public $code = 'paypal_pro_dp';
    public $title, $description, $enable, $_app;

    public function __construct() {
      global $order;

      $this->_app = new PayPal();
      $this->_app->loadLanguageFile('modules/DP/DP.php');

      $this->signature = 'paypal|paypal_pro_dp|' . $this->_app->getVersion() . '|2.3';
      $this->api_version = $this->_app->getApiVersion();

      $this->title = $this->_app->getDef('module_dp_title');
      $this->public_title = $this->_app->getDef('module_dp_public_title');
      $link = isset($GLOBALS['Admin']) ? $GLOBALS['Admin']->link('paypal.php', 'action=configure&module=DP') : '';
      $this->description = '<div align="center">' . $this->_app->drawButton($this->_app->getDef('module_dp_legacy_admin_app_button'), $link, 'primary', null, true) . '</div>';
      $this->sort_order = defined('PAYPAL_APP_DP_SORT_ORDER') ? PAYPAL_APP_DP_SORT_ORDER : 0;
      $this->enabled = defined('PAYPAL_APP_DP_STATUS') && in_array(PAYPAL_APP_DP_STATUS, ['1', '0']);
      $this->order_status = defined('PAYPAL_APP_DP_ORDER_STATUS_ID') && ((int)PAYPAL_APP_DP_ORDER_STATUS_ID > 0) ? (int)PAYPAL_APP_DP_ORDER_STATUS_ID : 0;

      if ( !defined('MODULE_PAYMENT_INSTALLED') || Text::is_empty(MODULE_PAYMENT_INSTALLED) || !in_array('paypal_express.php', explode(';', MODULE_PAYMENT_INSTALLED)) || !defined('PAYPAL_APP_EC_STATUS') || !in_array(PAYPAL_APP_EC_STATUS, ['1', '0']) ) {
        $this->description .= '<div class="alert alert-warning">' . $this->_app->getDef('module_dp_error_express_module') . '</div>';

        $this->enabled = false;
      }

      if ( defined('PAYPAL_APP_DP_STATUS') ) {
        if ( PAYPAL_APP_DP_STATUS == '0' ) {
          $this->title .= ' [Sandbox]';
          $this->public_title .= ' (' . $this->code . '; Sandbox)';
        }
      }

      if ( !function_exists('curl_init') ) {
        $this->description .= '<div class="alert alert-warning">' . $this->_app->getDef('module_dp_error_curl') . '</div>';

        $this->enabled = false;
      }

      if ( $this->enabled === true ) {
        if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
          if ( !$this->_app->hasCredentials('DP') ) {
            $this->description .= '<div class="alert alert-warning">' . $this->_app->getDef('module_dp_error_credentials') . '</div>';

            $this->enabled = false;
          }
        } else { // Payflow
          if ( !$this->_app->hasCredentials('DP', 'payflow') ) {
            $this->description .= '<div class="alert alert-warning">' . $this->_app->getDef('module_dp_error_credentials_payflow') . '</div>';

            $this->enabled = false;
          }
        }
      }

      if ( $this->enabled === true ) {
        if ( isset($order) && is_object($order) ) {
          $this->update_status();
        }
      }

      $this->cc_types = [
        'VISA' => 'Visa',
        'MASTERCARD' => 'MasterCard',
        'DISCOVER' => 'Discover Card',
        'AMEX' => 'American Express',
        'MAESTRO' => 'Maestro',
      ];
    }

    public function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)PAYPAL_APP_DP_ZONE > 0) ) {
        $check_query = $GLOBALS['db']->query("SELECT zone_id FROM zones_to_geo_zones WHERE geo_zone_id = " . (int)PAYPAL_APP_DP_ZONE . " and zone_country_id = " . (int)$customer_data->get('country_id', $order->delivery) . " order by zone_id");
        while ($check = $check_query->fetch_assoc()) {
          if (($check['zone_id'] < 1) || ($check['zone_id'] == $customer_data->get('zone_id', $order->delivery))) {
            return;
          }
        }

        $this->enabled = false;
      }
    }

    public function javascript_validation() {
      return false;
    }

    public function selection() {
      return [
        'id' => $this->code,
        'module' => $this->public_title,
      ];
    }

    public function pre_confirmation_check() {
      $GLOBALS['Template']->add_block('<style>.date-fields .form-control {width:auto;display:inline-block}</style>', 'header_tags');
      $GLOBALS['Template']->add_block($this->getSubmitCardDetailsJavascript(), 'footer_scripts');
    }

    public function confirmation() {
      global $order;

      $card_types = [];
      foreach ( $this->cc_types as $key => $value ) {
        if ($this->isCardAccepted($key)) {
          $card_types[] = [
            'id' => $key,
            'text' => $value,
          ];
        }
      }

      $today = getdate();

      $months = [];
      for ($i = 1; $i <= 12; $i++) {
        $months[] = ['id' => sprintf('%02d', $i), 'text' => sprintf('%02d', $i)];
      }

      $valid_from_years = [];
      for ($i = $today['year'] - 10; $i <= $today['year']; $i++) {
        $year = strftime('%Y', mktime(0, 0, 0, 2, 2, $i));
        $valid_from_years[] = ['id' => $year, 'text' => $year];
      }

      $expiration_years = [];
      for ($i = $today['year']; $i < $today['year']+10; $i++) {
        $year = strftime('%Y', mktime(0, 0, 0, 2, 2, $i));
        $expiration_years[] = ['id' => $year, 'text' => $year];
      }

      $content = '<table class="table" id="paypal_table_new_card">'
               . '  <tr>'
               . '    <td class="w-25">' . $this->_app->getDef('module_dp_field_card_type') . '</td>'
               . '    <td>' . new Select('cc_type', $card_types, ['id' => 'paypal_card_type']) . '</td>'
               . '  </tr>'
               . '  <tr>'
               . '    <td class="w-25">' . $this->_app->getDef('module_dp_field_card_owner') . '</td>'
               . '    <td>' . new Input('cc_owner', ['value' => $GLOBALS['customer_data']->get('name', $order->billing)]) . '</td>'
               . '  </tr>'
               . '  <tr>'
               . '    <td class="w-25">' . $this->_app->getDef('module_dp_field_card_number') . '</td>'
               . '    <td>' . new Input('cc_number_nh-dns', ['id' => 'paypal_card_num']) . '</td>'
               . '  </tr>'
               . '  <tr>'
               . '    <td class="w-25">' . $this->_app->getDef('module_dp_field_card_expires') . '</td>'
               . '    <td class="date-fields">' . new Select('cc_expires_month', $months) . '&nbsp;' . new Select('cc_expires_year', $expiration_years) . '</td>'
               . '  </tr>'
               . '  <tr>'
               . '    <td class="w-25">' . $this->_app->getDef('module_dp_field_card_cvc') . '</td>'
               . '    <td>'
                      . new Input('cc_cvc_nh-dns', ['size' => '5', 'maxlength' => '4'])
                      . ' <span id="cardSecurityCodeInfo" title="' . Text::output($this->_app->getDef('module_dp_field_card_cvc_info'))
                          . '" style="color: #084482; text-decoration: none; border-bottom: 1px dashed #084482; cursor: pointer;">'
                      . $this->_app->getDef('module_dp_field_card_cvc_info_link') . '</span></td>'
               . '  </tr>';

      if ( $this->isCardAccepted('MAESTRO') ) {
        $content .= '  <tr>'
                  . '    <td class="w-25">' . $this->_app->getDef('module_dp_field_card_valid_from') . '</td>'
                  . '    <td class="date-fields">'
                         . new Select('cc_starts_month', $months, ['id' => 'paypal_card_date_start']) . '&nbsp;'
                         . new Select('cc_starts_year', $valid_from_years) . '&nbsp;' . $this->_app->getDef('module_dp_field_card_valid_from_info') . '</td>'
                  . '  </tr>'
                  . '  <tr>'
                  . '    <td class="w-25">' . $this->_app->getDef('module_dp_field_card_issue_number') . '</td>'
                  . '    <td>'
                    . new Input('cc_issue_nh-dns', ['id' => 'paypal_card_issue', 'size' => '3', 'maxlength' => '2']) . '&nbsp;'
                    . $this->_app->getDef('module_dp_field_card_issue_number_info') . '</td>'
                  . '  </tr>';
      }

      $content .= '</table>';

      $content .= $this->getSubmitCardDetailsJavascript();

      return ['title' => $content];
    }

    public function process_button() {
      return false;
    }

    public function before_process() {
      if ( PAYPAL_APP_GATEWAY == '1' ) {
        $this->before_process_paypal();
      } else {
        $this->before_process_payflow();
      }
    }

    public function before_process_paypal() {
      global $order, $response_array, $customer_data;

      if ( !empty($_POST['cc_owner']) && !empty($_POST['cc_number_nh-dns']) && isset($_POST['cc_type']) && $this->isCardAccepted($_POST['cc_type']) ) {
        $customer_data->get('country', $order->billing);
        $params = [
          'AMT' => $GLOBALS['currencies']->format_raw($order->info['total']),
          'CREDITCARDTYPE' => $_POST['cc_type'],
          'ACCT' => $_POST['cc_number_nh-dns'],
          'EXPDATE' => $_POST['cc_expires_month'] . $_POST['cc_expires_year'],
          'CVV2' => $_POST['cc_cvc_nh-dns'],
          'FIRSTNAME' => substr($_POST['cc_owner'], 0, strpos($_POST['cc_owner'], ' ')),
          'LASTNAME' => substr($_POST['cc_owner'], strpos($_POST['cc_owner'], ' ')+1),
          'STREET' => $customer_data->get('street_address', $order->billing),
          'STREET2' => $customer_data->get('suburb', $order->billing),
          'CITY' => $customer_data->get('city', $order->billing),
          'STATE' => Zone::fetch_code(
            $customer_data->get('country_id', $order->billing),
            $customer_data->get('zone_id', $order->billing),
            $customer_data->get('state', $order->billing)),
          'COUNTRYCODE' => $customer_data->get('country_iso_code_2', $order->billing),
          'ZIP' => $customer_data->get('postcode', $order->billing),
          'EMAIL' => $customer_data->get('email_address', $order->customer),
          'SHIPTOPHONENUM' => $customer_data->get('telephone', $order->customer),
          'CURRENCYCODE' => $order->info['currency'],
        ];

        if ( $_POST['cc_type'] == 'MAESTRO' ) {
          $params['STARTDATE'] = $_POST['cc_starts_month'] . $_POST['cc_starts_year'];
          $params['ISSUENUMBER'] = $_POST['cc_issue_nh-dns'];
        }

        if ( is_numeric($_SESSION['sendto']) && ($_SESSION['sendto'] > 0) ) {
          $customer_data->get('country', $order->delivery);
          $params['SHIPTONAME'] = $customer_data->get('name', $order->delivery);
          $params['SHIPTOSTREET'] = $customer_data->get('street_address', $order->delivery);
          $params['SHIPTOSTREET2'] = $customer_data->get('suburb', $order->delivery);
          $params['SHIPTOCITY'] = $customer_data->get('city', $order->delivery);
          $params['SHIPTOSTATE'] = Zone::fetch_code(
            $customer_data->get('country_id', $order->delivery),
            $customer_data->get('zone_id', $order->delivery),
            $customer_data->get('state', $order->delivery));
          $params['SHIPTOCOUNTRYCODE'] = $customer_data->get('country_iso_code_2', $order->delivery);
          $params['SHIPTOZIP'] = $customer_data->get('postcode', $order->delivery);
        }

        $item_params = [];

        $line_item_no = 0;

        foreach ( $order->products as $product ) {
          $item_params['L_NAME' . $line_item_no] = $product['name'];
          $item_params['L_AMT' . $line_item_no] = $GLOBALS['currencies']->format_raw($product['final_price']);
          $item_params['L_NUMBER' . $line_item_no] = $product['id'];
          $item_params['L_QTY' . $line_item_no] = $product['qty'];

          $line_item_no++;
        }

        $items_total = $GLOBALS['currencies']->format_raw($order->info['subtotal']);

        foreach ( $order->totals as $ot ) {
          if ( !in_array($ot['code'], ['ot_subtotal', 'ot_shipping', 'ot_tax', 'ot_total']) ) {
            $item_params['L_NAME' . $line_item_no] = $ot['title'];
            $item_params['L_AMT' . $line_item_no] = $GLOBALS['currencies']->format_raw($ot['value']);

            $items_total += $GLOBALS['currencies']->format_raw($ot['value']);

            $line_item_no++;
          }
        }

        $item_params['ITEMAMT'] = $items_total;
        $item_params['TAXAMT'] = $GLOBALS['currencies']->format_raw($order->info['tax']);
        $item_params['SHIPPINGAMT'] = $GLOBALS['currencies']->format_raw($order->info['shipping_cost']);

        if ( $GLOBALS['currencies']->format_raw($item_params['ITEMAMT'] + $item_params['TAXAMT'] + $item_params['SHIPPINGAMT']) == $params['AMT'] ) {
          $params = array_merge($params, $item_params);
        }

        $response_array = $this->_app->getApiResult('DP', 'DoDirectPayment', $params);

        if ( !in_array($response_array['ACK'], ['Success', 'SuccessWithWarning']) ) {
          Href::redirect($GLOBALS['Linker']->build('shopping_cart.php', 'error_message=' . stripslashes($response_array['L_LONGMESSAGE0'])));
        }
      } else {
        Href::redirect($GLOBALS['Linker']->build('checkout_confirmation.php', 'error_message=' . $this->_app->getDef('module_dp_error_all_fields_required')));
      }
    }

    public function before_process_payflow() {
      global $order, $response_array, $customer_data;

      if ( !empty($_POST['cc_owner']) && !empty($_POST['cc_number_nh-dns']) && isset($_POST['cc_type']) && $this->isCardAccepted($_POST['cc_type']) ) {
        $customer_data->get('country', $order->billing);
        $params = [
          'AMT' => $GLOBALS['currencies']->format_raw($order->info['total']),
          'CURRENCY' => $order->info['currency'],
          'BILLTOFIRSTNAME' => substr($_POST['cc_owner'], 0, strpos($_POST['cc_owner'], ' ')),
          'BILLTOLASTNAME' => substr($_POST['cc_owner'], strpos($_POST['cc_owner'], ' ')+1),
          'BILLTOSTREET' => $customer_data->get('street_address', $order->billing),
          'BILLTOSTREET2' => $customer_data->get('suburb', $order->billing),
          'BILLTOCITY' => $customer_data->get('city', $order->billing),
          'BILLTOSTATE' => Zone::fetch_code(
            $customer_data->get('country_id', $order->billing),
            $customer_data->get('zone_id', $order->billing),
            $customer_data->get('state', $order->billing)),
          'BILLTOCOUNTRY' => $customer_data->get('country_iso_code_2', $order->billing),
          'BILLTOZIP' => $customer_data->get('postcode', $order->billing),
          'EMAIL' => $customer_data->get('email_address', $order->customer),
          'ACCT' => $_POST['cc_number_nh-dns'],
          'EXPDATE' => $_POST['cc_expires_month'] . $_POST['cc_expires_year'],
          'CVV2' => $_POST['cc_cvc_nh-dns'],
        ];

        if ( is_numeric($_SESSION['sendto']) && ($_SESSION['sendto'] > 0) ) {
          $customer_data->get('country', $order->delivery);
          $params['SHIPTOFIRSTNAME'] = $customer_data->get('firstname', $order->delivery);
          $params['SHIPTOLASTNAME'] = $customer_data->get('lastname', $order->delivery);
          $params['SHIPTOSTREET'] = $customer_data->get('street_address', $order->delivery);
          $params['SHIPTOSTREET2'] = $customer_data->get('suburb', $order->delivery);
          $params['SHIPTOCITY'] = $customer_data->get('city', $order->delivery);
          $params['SHIPTOSTATE'] = Zone::fetch_code(
            $customer_data->get('country_id', $order->delivery),
            $customer_data->get('zone_id', $order->delivery),
            $customer_data->get('state', $order->delivery));
          $params['SHIPTOCOUNTRY'] = $customer_data->get('country_iso_code_2', $order->delivery);
          $params['SHIPTOZIP'] = $customer_data->get('postcode', $order->delivery);
        }

        $item_params = [];

        $line_item_no = 0;

        foreach ($order->products as $product) {
          $item_params['L_NAME' . $line_item_no] = $product['name'];
          $item_params['L_COST' . $line_item_no] = $GLOBALS['currencies']->format_raw($product['final_price']);
          $item_params['L_QTY' . $line_item_no] = $product['qty'];

          $line_item_no++;
        }

        $items_total = $GLOBALS['currencies']->format_raw($order->info['subtotal']);

        foreach ($order->totals as $ot) {
          if ( !in_array($ot['code'], ['ot_subtotal', 'ot_shipping', 'ot_tax', 'ot_total']) ) {
            $item_params['L_NAME' . $line_item_no] = $ot['title'];
            $item_params['L_COST' . $line_item_no] = $GLOBALS['currencies']->format_raw($ot['value']);
            $item_params['L_QTY' . $line_item_no] = 1;

            $items_total += $GLOBALS['currencies']->format_raw($ot['value']);

            $line_item_no++;
          }
        }

        $item_params['ITEMAMT'] = $items_total;
        $item_params['TAXAMT'] = $GLOBALS['currencies']->format_raw($order->info['tax']);
        $item_params['FREIGHTAMT'] = $GLOBALS['currencies']->format_raw($order->info['shipping_cost']);

        if ( $GLOBALS['currencies']->format_raw($item_params['ITEMAMT'] + $item_params['TAXAMT'] + $item_params['FREIGHTAMT']) == $params['AMT'] ) {
          $params = array_merge($params, $item_params);
        }

        $params['_headers'] = [
          'X-VPS-REQUEST-ID: ' . md5($_SESSION['cartID'] . session_id() . $GLOBALS['currencies']->format_raw($order->info['total'])),
          'X-VPS-CLIENT-TIMEOUT: 45',
          'X-VPS-VIT-INTEGRATION-PRODUCT: Phoenix',
          'X-VPS-VIT-INTEGRATION-VERSION: 1.0',
        ];

        $response_array = $this->_app->getApiResult('DP', 'PayflowPayment', $params);

        if ( $response_array['RESULT'] != '0' ) {
          switch ( $response_array['RESULT'] ) {
            case '1':
            case '26':
              $error_message = $this->_app->getDef('module_dp_error_configuration');
              break;

            case '7':
              $error_message = $this->_app->getDef('module_dp_error_address');
              break;

            case '12':
              $error_message = $this->_app->getDef('module_dp_error_declined');
              break;

            case '23':
            case '24':
              $error_message = $this->_app->getDef('module_dp_error_invalid_card');
              break;

            default:
              $error_message = $this->_app->getDef('module_dp_error_general');
              break;
          }

          Href::redirect($GLOBALS['Linker']->build('checkout_confirmation.php', 'error_message=' . $error_message));
        }
      } else {
        Href::redirect($GLOBALS['Linker']->build('checkout_confirmation.php', 'error_message=' . $this->_app->getDef('module_dp_error_all_fields_required')));
      }
    }

    public function after_process() {
      if ( PAYPAL_APP_GATEWAY == '1' ) {
        $this->after_process_paypal();
      } else {
        $this->after_process_payflow();
      }
    }

    public function after_process_paypal() {
      global $response_array, $order_id;

      $details = $this->_app->getApiResult('APP', 'GetTransactionDetails', ['TRANSACTIONID' => $response_array['TRANSACTIONID']], (PAYPAL_APP_DP_STATUS == '1') ? 'live' : 'sandbox');

      $result = 'Transaction ID: ' . htmlspecialchars($response_array['TRANSACTIONID']) . "\n";

      if ( in_array($details['ACK'], ['Success', 'SuccessWithWarning']) ) {
        $result .= 'Payer Status: ' . htmlspecialchars($details['PAYERSTATUS']) . "\n"
                 . 'Address Status: ' . htmlspecialchars($details['ADDRESSSTATUS']) . "\n"
                 . 'Payment Status: ' . htmlspecialchars($details['PAYMENTSTATUS']) . "\n"
                 . 'Payment Type: ' . htmlspecialchars($details['PAYMENTTYPE']) . "\n"
                 . 'Pending Reason: ' . htmlspecialchars($details['PENDINGREASON']) . "\n";
      }

      $result .= 'AVS Code: ' . htmlspecialchars($response_array['AVSCODE']) . "\n"
               . 'CVV2 Match: ' . htmlspecialchars($response_array['CVV2MATCH']);

      $sql_data = [
        'orders_id' => $order_id,
        'orders_status_id' => PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID,
        'date_added' => 'NOW()',
        'customer_notified' => '0',
        'comments' => $result,
      ];

      $GLOBALS['db']->perform('orders_status_history', $sql_data);
    }

    public function after_process_payflow() {
      global $order_id, $response_array;

      $details = $this->_app->getApiResult('APP', 'PayflowInquiry', ['ORIGID' => $response_array['PNREF']], (PAYPAL_APP_DP_STATUS == '1') ? 'live' : 'sandbox');

      $result = 'Transaction ID: ' . htmlspecialchars($response_array['PNREF']) . "\n"
              . 'Gateway: Payflow' . "\n"
              . 'PayPal ID: ' . htmlspecialchars($response_array['PPREF']) . "\n"
              . 'Response: ' . htmlspecialchars($response_array['RESPMSG']) . "\n";

      if ( isset($details['RESULT']) && ($details['RESULT'] == '0') ) {
        $pending_reason = $details['TRANSSTATE'];
        $payment_status = null;

        switch ( $details['TRANSSTATE'] ) {
          case '3':
            $pending_reason = 'authorization';
            $payment_status = 'Pending';
            break;

          case '4':
            $pending_reason = 'other';
            $payment_status = 'In-Progress';
            break;

          case '6':
            $pending_reason = 'scheduled';
            $payment_status = 'Pending';
            break;

          case '8':
          case '9':
            $pending_reason = 'None';
            $payment_status = 'Completed';
            break;
        }

        if ( isset($payment_status) ) {
          $result .= 'Payment Status: ' . htmlspecialchars($payment_status) . "\n";
        }

        $result .= 'Pending Reason: ' . htmlspecialchars($pending_reason) . "\n";
      }

      switch ( $response_array['AVSADDR'] ) {
        case 'Y':
          $result .= 'AVS Address: Match' . "\n";
          break;

        case 'N':
          $result .= 'AVS Address: No Match' . "\n";
          break;
      }

      switch ( $response_array['AVSZIP'] ) {
        case 'Y':
          $result .= 'AVS ZIP: Match' . "\n";
          break;

        case 'N':
          $result .= 'AVS ZIP: No Match' . "\n";
          break;
      }

      switch ( $response_array['IAVS'] ) {
        case 'Y':
          $result .= 'IAVS: International' . "\n";
          break;

        case 'N':
          $result .= 'IAVS: USA' . "\n";
          break;
      }

      switch ( $response_array['CVV2MATCH'] ) {
        case 'Y':
          $result .= 'CVV2: Match' . "\n";
          break;

        case 'N':
          $result .= 'CVV2: No Match' . "\n";
          break;
      }

      $sql_data = [
        'orders_id' => $order_id,
        'orders_status_id' => PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID,
        'date_added' => 'NOW()',
        'customer_notified' => '0',
        'comments' => $result,
      ];

      $GLOBALS['db']->perform('orders_status_history', $sql_data);
    }

    public function get_error() {
      return false;
    }

    public function check() {
      $check_query = $GLOBALS['db']->query("SELECT configuration_value FROM configuration WHERE configuration_key = 'PAYPAL_APP_DP_STATUS'");
      if ( $check = $check_query->fetch_assoc() ) {
        return !Text::is_empty($check['configuration_value']);
      }

      return false;
    }

    public function install() {
      Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', 'action=configure&subaction=install&module=DP'));
    }

    public function remove() {
      Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', 'action=configure&subaction=uninstall&module=DP'));
    }

    public function keys() {
      return ['PAYPAL_APP_DP_SORT_ORDER'];
    }

    public function isCardAccepted($card) {
      static $cards;

      if ( !isset($cards) ) {
        $cards = explode(';', PAYPAL_APP_DP_CARDS);
      }

      return isset($this->cc_types[$card]) && in_array(strtolower($card), $cards);
    }

    public function getSubmitCardDetailsJavascript() {
      $js = <<<"EOD"
<script>
if ( typeof jQuery == 'undefined' ) {
  document.write('<scr' + 'ipt src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></scr' + 'ipt>');
}
</script>

<script>
$(function() {
  if ( typeof($('#paypal_table_new_card').parent().closest('table').attr('width')) == 'undefined' ) {
    $('#paypal_table_new_card').parent().closest('table').attr('width', '100%');
  }

  paypalShowNewCardFields();

  $('#paypal_card_type').change(function() {
    var selected = $(this).val();

    if ( $('#paypal_card_date_start').length > 0 ) {
      if ( selected == 'MAESTRO' ) {
        $('#paypal_card_date_start').parent().parent().show();
      } else {
        $('#paypal_card_date_start').parent().parent().hide();
      }
    }

    if ( $('#paypal_card_issue').length > 0 ) {
      if ( selected == 'MAESTRO' ) {
        $('#paypal_card_issue').parent().parent().show();
      } else {
        $('#paypal_card_issue').parent().parent().hide();
      }
    }
  });

  $('#cardSecurityCodeInfo').tooltip();
});

function paypalShowNewCardFields() {
  var selected = $('#paypal_card_type').val();

  if ( $('#paypal_card_date_start').length > 0 ) {
    if ( selected != 'MAESTRO' ) {
      $('#paypal_card_date_start').parent().parent().hide();
    }
  }

  if ( $('#paypal_card_issue').length > 0 ) {
    if ( selected != 'MAESTRO' ) {
      $('#paypal_card_issue').parent().parent().hide();
    }
  }
}
</script>
EOD;

      return $js;
    }

  }
