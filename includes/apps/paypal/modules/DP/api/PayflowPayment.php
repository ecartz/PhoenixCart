<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  function PayPal_DP_Api_PayflowPayment($PayPal, $server, $extra_params) {
    $api_url = ( $server == 'live' )
             ? 'https://payflowpro.paypal.com'
             : 'https://pilot-payflowpro.paypal.com';

    $params = [
      'USER' => $PayPal->getCredentials('DP', ($PayPal->hasCredentials('DP', 'payflow_user') ? 'payflow_user' : 'payflow_vendor')),
      'VENDOR' => $PayPal->getCredentials('DP', 'payflow_vendor'),
      'PARTNER' => $PayPal->getCredentials('DP', 'payflow_partner'),
      'PWD' => $PayPal->getCredentials('DP', 'payflow_password'),
      'TENDER' => 'C',
      'TRXTYPE' => (PAYPAL_APP_DP_TRANSACTION_METHOD == '1') ? 'S' : 'A',
      'CUSTIP' => Request::get_ip(),
      'BUTTONSOURCE' => $PayPal->getIdentifier(),
    ];

    if ( !empty($extra_params) && is_array($extra_params) ) {
      $params = array_merge($params, $extra_params);
    }

    $headers = [];
    if ( isset($params['_headers']) ) {
      $headers = $params['_headers'];

      unset($params['_headers']);
    }

    $post_string = '';

    foreach ($params as $key => $value) {
      $post_string .= $key . '[' . strlen(trim($value)) . ']=' . trim($value) . '&';
    }

    $post_string = substr($post_string, 0, -strlen('&'));

    $response = $PayPal->makeApiCall($api_url, $post_string, $headers);
    parse_str($response, $response_array);

    return [
      'res' => $response_array,
      'success' => ($response_array['RESULT'] == '0'),
      'req' => $params,
    ];
  }
