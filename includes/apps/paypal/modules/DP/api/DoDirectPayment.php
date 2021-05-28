<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  function PayPal_DP_Api_DoDirectPayment($PayPal, $server, $extra_params) {
    if ( $server === 'live' ) {
      $api_url = 'https://api-3t.paypal.com/nvp';
    } else {
      $api_url = 'https://api-3t.sandbox.paypal.com/nvp';
    }

    $params = [
      'USER' => $PayPal->getCredentials('DP', 'username'),
      'PWD' => $PayPal->getCredentials('DP', 'password'),
      'SIGNATURE' => $PayPal->getCredentials('DP', 'signature'),
      'VERSION' => $PayPal->getApiVersion(),
      'METHOD' => 'DoDirectPayment',
      'PAYMENTACTION' => (PAYPAL_APP_DP_TRANSACTION_METHOD == '1') ? 'Sale' : 'Authorization',
      'IPADDRESS' => Request::get_ip(),
      'BUTTONSOURCE' => $PayPal->getIdentifier(),
    ];

    if ( !empty($extra_params) && is_array($extra_params) ) {
      $params = array_merge($params, $extra_params);
    }

    $post_string = '';
    foreach ( $params as $key => $value ) {
      $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
    }

    $post_string = substr($post_string, 0, -strlen('&'));

    $response = $PayPal->makeApiCall($api_url, $post_string);
    parse_str($response, $response_array);

    return [
      'res' => $response_array,
      'success' => in_array($response_array['ACK'], ['Success', 'SuccessWithWarning']),
      'req' => $params,
    ];
  }
