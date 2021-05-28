<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  $content = 'configure.php';

  $modules = $PayPal->getModules();
  $modules[] = 'G';

  $default_module = 'G';

  foreach ( $modules as $m ) {
    if ( $PayPal->isInstalled($m) ) {
      $default_module = $m;
      break;
    }
  }

  $current_module = (isset($_GET['module']) && in_array($_GET['module'], $modules)) ? $_GET['module'] : $default_module;

  if ( !defined('PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID') ) {
    $check_query = $GLOBALS['db']->query("SELECT orders_status_id FROM orders_status WHERE orders_status_name = 'PayPal [Transactions]' LIMIT 1");
    if ($check = $check_query->fetch_assoc()) {
      $status_id = $check['orders_status_id'];
    } else {
      $status = $GLOBALS['db']->query("SELECT MAX(orders_status_id) + 1 AS id FROM orders_status")->fetch_assoc();

      $sql_data = [
        'orders_status_id' => (int)$status['id'],
        'orders_status_name' => 'PayPal [Transactions]',
        'public_flag' => 0,
        'downloads_flag' => 0,
      ];

      foreach (language::load_all() as $language) {
        $sql_data['language_id'] = (int)$language['id'];
        $GLOBALS['db']->perform('orders_status', $sql_data);
      }

      $status_id = $status['id'];
    }

    $PayPal->saveParameter('PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID', $status_id);
  }

  if ( !defined('PAYPAL_APP_VERIFY_SSL') ) {
    $PayPal->saveParameter('PAYPAL_APP_VERIFY_SSL', '1');
  }

  if ( !defined('PAYPAL_APP_PROXY') ) {
    $PayPal->saveParameter('PAYPAL_APP_PROXY', '');
  }

  if ( !defined('PAYPAL_APP_GATEWAY') ) {
    $PayPal->saveParameter('PAYPAL_APP_GATEWAY', '1');
  }

  if ( !defined('PAYPAL_APP_LOG_TRANSACTIONS') ) {
    $PayPal->saveParameter('PAYPAL_APP_LOG_TRANSACTIONS', '1');
  }
