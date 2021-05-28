<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  if ( !isset($_GET['type']) || !in_array($_GET['type'], ['live', 'sandbox']) ) {
    $PayPal->addAlert($PayPal->getDef('alert_onboarding_account_type_error'), 'error');
    return;
  }

  $parameters = [
    'return_url' => Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'start', 'subaction' => 'retrieve']),
    'type' => $_GET['type'],
    'site_url' => Guarantor::ensure_global('Admin')->link('index.php'),
    'site_currency' => DEFAULT_CURRENCY,
  ];

  if (!Text::is_empty(STORE_OWNER_EMAIL_ADDRESS) && (filter_var(STORE_OWNER_EMAIL_ADDRESS, FILTER_VALIDATE_EMAIL) !== false)) {
    $parameters['email'] = STORE_OWNER_EMAIL_ADDRESS;
  }

  if (!Text::is_empty(STORE_OWNER)) {
    $name_array = explode(' ', STORE_OWNER, 2);

    $parameters['firstname'] = $name_array[0];
    $parameters['surname'] = $name_array[1] ?? '';
  }

  if (!Text::is_empty(STORE_NAME)) {
    $parameters['site_name'] = STORE_NAME;
  }

  $result_string = $PayPal->makeApiCall('https://www.oscommerce.com/index.php?RPC&Website&Index&PayPalStart', $parameters);

  if ( empty($result_string) || !Text::is_prefixed_by($result_string, 'rpcStatus') ) {
    $PayPal->addAlert($PayPal->getDef('alert_onboarding_connection_error'), 'error');
    return;
  }

  $results = [];
  foreach ( explode("\n", $result_string) as $r ) {
    $key = explode('=', $r, 2);

    if ( is_array($key) && !empty($key[0]) && !empty($key[1]) ) {
      $results[$key[0]] = $key[1];
    }
  }

  if ( isset($results['rpcStatus'], $results['merchant_id'], $results['redirect_url'], $results['secret'])
    && ($results['rpcStatus'] === '1')
    && (preg_match('{^[A-Za-z0-9]{32}$}', $results['merchant_id']) === 1) )
  {
    $PayPal->saveParameter('PAYPAL_APP_START_MERCHANT_ID', $results['merchant_id']);
    $PayPal->saveParameter('PAYPAL_APP_START_SECRET', $results['secret']);

    Href::redirect($results['redirect_url']);
  } else {
    $PayPal->addAlert($PayPal->getDef('alert_onboarding_initialization_error'), 'error');
  }
