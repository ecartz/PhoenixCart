<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  chdir('../../../../');
  require 'includes/application_top.php';

  if ( !defined('PAYPAL_APP_PS_STATUS') || !in_array(PAYPAL_APP_PS_STATUS, ['1', '0']) ) {
    exit();
  }

  $_SESSION['payment'] = 'paypal_standard';
  $paypal_standard = new paypal_standard();

  $result = false;

  $seller_accounts = [$paypal_standard->_app->getCredentials('PS', 'email')];

  if ( !Text::is_empty($paypal_standard->_app->getCredentials('PS', 'email_primary')) ) {
    $seller_accounts[] = $paypal_standard->_app->getCredentials('PS', 'email_primary');
  }

  if ( (isset($_POST['receiver_email']) && in_array($_POST['receiver_email'], $seller_accounts)) || (isset($_POST['business']) && in_array($_POST['business'], $seller_accounts)) ) {
    $parameters = 'cmd=_notify-validate&';

    foreach ( $_POST as $key => $value ) {
      if ( $key != 'cmd' ) {
        $parameters .= $key . '=' . urlencode(stripslashes($value)) . '&';
      }
    }

    $parameters = substr($parameters, 0, -strlen('&'));

    $result = $paypal_standard->_app->makeApiCall($paypal_standard->form_action_url, $parameters);
  }

  $log_params = [];
  foreach ( $_POST as $key => $value ) {
    $log_params[$key] = stripslashes($value);
  }

  foreach ( $_GET as $key => $value ) {
    $log_params['GET ' . $key] = stripslashes($value);
  }

  $paypal_standard->_app->log('PS', '_notify-validate', ($result == 'VERIFIED') ? 1 : -1, $log_params, $result, (PAYPAL_APP_PS_STATUS == '1') ? 'live' : 'sandbox', true);

  if ( $result == 'VERIFIED' ) {
    $paypal_standard->verifyTransaction($_POST, true);

    $order_id = (int)$_POST['invoice'];
    $customer_id = (int)$_POST['custom'];
    if (!isset($customer) || !($customer instanceof customer) || ($customer_id != $customer->get_id())) {
      $customer = new customer($customer_id);
    }

    $check_query = $db->query("SELECT orders_status FROM orders WHERE orders_id = " . (int)$order_id . " AND customers_id = " . (int)$customer_id);

    if ($check = $check_query->fetch_assoc()) {
      if ( $check['orders_status'] == PAYPAL_APP_PS_PREPARE_ORDER_STATUS_ID ) {
        $order = new order($order_id);
        $order->info['order_status'] = DEFAULT_ORDERS_STATUS_ID;

        if ( PAYPAL_APP_PS_ORDER_STATUS_ID > 0 ) {
          $order->info['order_status'] = PAYPAL_APP_PS_ORDER_STATUS_ID;
        }

        $db->query("UPDATE orders SET orders_status = " . (int)$order->info['order_status'] . ", last_modified = NOW() WHERE orders_id = " . (int)$order_id);

        if ('true' === DOWNLOAD_ENABLED) {
          $downloads_query = $db->query(sprintf(<<<'EOSQL'
SELECT opd.orders_products_filename
 FROM orders o
  INNER JOIN orders_products op ON o.orders_id = op.orders_id
  INNER JOIN orders_products_download opd ON op.orders_products_id = opd.orders_products_id
 WHERE o.orders_id = %d AND o.customers_id = %d AND opd.orders_products_filename != ''
EOSQL
            , (int)$order_id, (int)$customer_id));

          switch (mysqli_num_rows($downloads_query)) {
            case 0:
              $order->content_type = 'physical';
              break;
            case count($order->products):
              $order->content_type = 'virtual';
              break;
            default:
              $order->content_type = 'mixed';
          }
        } else {
          $order->content_type = 'physical';
        }

        $hooks->register_pipeline('after');
        include 'includes/system/segments/checkout/insert_history.php';

        $db->query("DELETE FROM customers_basket WHERE customers_id = " . (int)$customer_id);
        $db->query("DELETE FROM customers_basket_attributes WHERE customers_id = " . (int)$customer_id);
      }
    }
  }

  Session::destroy();

  require 'includes/application_bottom.php';
