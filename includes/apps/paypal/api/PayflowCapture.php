<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  function PayPal_Api_PayflowCapture($PayPal, $server, $extra_params) {
    if ( $server == 'live' ) {
      $api_url = 'https://payflowpro.paypal.com';
    } else {
      $api_url = 'https://pilot-payflowpro.paypal.com';
    }

    $params = [
      'USER' => $PayPal->hasCredentials('DP', 'payflow_user') ? $PayPal->getCredentials('DP', 'payflow_user') : $PayPal->getCredentials('DP', 'payflow_vendor'),
      'VENDOR' => $PayPal->getCredentials('DP', 'payflow_vendor'),
      'PARTNER' => $PayPal->getCredentials('DP', 'payflow_partner'),
      'PWD' => $PayPal->getCredentials('DP', 'payflow_password'),
      'TENDER' => 'C',
      'TRXTYPE' => 'D',
    ];

    if ( is_array($extra_params) && !empty($extra_params) ) {
      $params = array_merge($params, $extra_params);
    }

    $post_string = '';
    foreach ($params as $key => $value) {
      $post_string .= $key . '[' . strlen(trim($value)) . ']=' . trim($value) . '&';
    }

    $post_string = substr($post_string, 0, -1);

    parse_str($PayPal->makeApiCall($api_url, $post_string), $responses);

    return [
      'res' => $responses,
      'success' => ($responses['RESULT'] == '0'),
      'req' => $params,
    ];
  }
