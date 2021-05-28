<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  function PayPal_Api_GetTransactionDetails($PayPal, $server, $extra_params) {
    if ( $server == 'live' ) {
      $api_url = 'https://api-3t.paypal.com/nvp';
    } else {
      $api_url = 'https://api-3t.sandbox.paypal.com/nvp';
    }

    $params = [
      'USER' => $PayPal->getApiCredentials($server, 'username'),
      'PWD' => $PayPal->getApiCredentials($server, 'password'),
      'SIGNATURE' => $PayPal->getApiCredentials($server, 'signature'),
      'VERSION' => $PayPal->getApiVersion(),
      'METHOD' => 'GetTransactionDetails',
    ];

    if ( is_array($extra_params) && !empty($extra_params) ) {
      $params = array_merge($params, $extra_params);
    }

    $post_string = '';
    foreach ( $params as $key => $value ) {
      $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
    }

    $post_string = substr($post_string, 0, -1);

    parse_str($PayPal->makeApiCall($api_url, $post_string), $responses);

    return [
      'res' => $responses,
      'success' => in_array($responses['ACK'], ['Success', 'SuccessWithWarning']),
      'req' => $params,
    ];
  }
