<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  function PayPal_LOGIN_Api_GrantToken($PayPal, $server, $extra_params) {
    if ( $server == 'live' ) {
      $api_url = 'https://api.paypal.com/v1/identity/openidconnect/tokenservice';
    } else {
      $api_url = 'https://api.sandbox.paypal.com/v1/identity/openidconnect/tokenservice';
    }

    $params = [
      'client_id' => (PAYPAL_APP_LOGIN_STATUS == '1') ? PAYPAL_APP_LOGIN_LIVE_CLIENT_ID : PAYPAL_APP_LOGIN_SANDBOX_CLIENT_ID,
      'client_secret' => (PAYPAL_APP_LOGIN_STATUS == '1') ? PAYPAL_APP_LOGIN_LIVE_SECRET : PAYPAL_APP_LOGIN_SANDBOX_SECRET,
      'grant_type' => 'authorization_code',
    ];

    if ( is_array($extra_params) && !empty($extra_params) ) {
      $params = array_merge($params, $extra_params);
    }

    $post_string = '';
    foreach ( $params as $key => $value ) {
      $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
    }

    $post_string = substr($post_string, 0, -1);

    $response = json_decode($PayPal->makeApiCall($api_url, $post_string), true);

    return [
      'res' => $response,
      'success' => (is_array($response) && !isset($response['error'])),
      'req' => $params,
    ];
  }
