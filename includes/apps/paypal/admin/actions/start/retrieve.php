<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  $params = [
    'merchant_id' => PAYPAL_APP_START_MERCHANT_ID,
    'secret' => PAYPAL_APP_START_SECRET,
  ];

  $result_string = $PayPal->makeApiCall('https://www.oscommerce.com/index.php?RPC&Website&Index&PayPalStart&action=retrieve', $params);
  $result = [];

  if ( $result_string && (Text::is_prefixed_by($result_string, 'rpcStatus')) ) {
    $raw = explode("\n", $result_string);

    foreach ( $raw as $r ) {
      $key = explode('=', $r, 2);

      if ( is_array($key) && !empty($key[0]) && !empty($key[1]) ) {
        $result[$key[0]] = $key[1];
      }
    }

    if ( isset($result['rpcStatus'], $result['account_type'], $result['api_username'], $result['api_password'], $result['api_signature'])
      && ($result['rpcStatus'] === '1') && in_array($result['account_type'], ['live', 'sandbox']) )
    {
      if ( $result['account_type'] == 'live' ) {
        $param_prefix = 'PAYPAL_APP_LIVE_';
      } else {
        $param_prefix = 'PAYPAL_APP_SANDBOX_';
      }

      $PayPal->saveParameter($param_prefix . 'SELLER_EMAIL', str_replace('_api1.', '@', $result['api_username']));
      $PayPal->saveParameter($param_prefix . 'SELLER_EMAIL_PRIMARY', str_replace('_api1.', '@', $result['api_username']));
      $PayPal->saveParameter($param_prefix . 'MERCHANT_ID', $result['account_id']);
      $PayPal->saveParameter($param_prefix . 'API_USERNAME', $result['api_username']);
      $PayPal->saveParameter($param_prefix . 'API_PASSWORD', $result['api_password']);
      $PayPal->saveParameter($param_prefix . 'API_SIGNATURE', $result['api_signature']);

      $PayPal->deleteParameter('PAYPAL_APP_START_MERCHANT_ID');
      $PayPal->deleteParameter('PAYPAL_APP_START_SECRET');

      $PayPal->addAlert($PayPal->getDef('alert_onboarding_success'), 'success');

      Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'credentials']));
    } else {
      $PayPal->addAlert($PayPal->getDef('alert_onboarding_retrieve_error'), 'error');
    }
  } else {
    $PayPal->addAlert($PayPal->getDef('alert_onboarding_retrieve_connection_error'), 'error');
  }
