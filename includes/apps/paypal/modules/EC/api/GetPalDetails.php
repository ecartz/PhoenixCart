<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  function PayPal_EC_Api_GetPalDetails($PayPal, $server, $extra_params = null) {
    if ( $server == 'live' ) {
      $api_url = 'https://api-3t.paypal.com/nvp';
    } else {
      $api_url = 'https://api-3t.sandbox.paypal.com/nvp';
    }

    $params = [
      'VERSION' => $PayPal->getApiVersion(),
      'METHOD' => 'GetPalDetails',
      'USER' => $PayPal->getCredentials('EC', 'username'),
      'PWD' => $PayPal->getCredentials('EC', 'password'),
      'SIGNATURE' => $PayPal->getCredentials('EC', 'signature'),
    ];

    $post_string = '';
    foreach ( $params as $key => $value ) {
      $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
    }

    $post_string = substr($post_string, 0, -1);

    parse_str($PayPal->makeApiCall($api_url, $post_string), $response);

    return [
      'res' => $response,
      'success' => isset($response['PAL']),
      'req' => $params,
    ];
  }
