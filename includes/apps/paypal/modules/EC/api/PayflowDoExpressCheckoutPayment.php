<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  function PayPal_EC_Api_PayflowDoExpressCheckoutPayment($PayPal, $server, $extra_params) {
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
      'TENDER' => 'P',
      'TRXTYPE' => (PAYPAL_APP_DP_TRANSACTION_METHOD == '1') ? 'S' : 'A',
      'ACTION' => 'D',
      'BUTTONSOURCE' => $PayPal->getIdentifier(),
    ];

    if ( is_array($extra_params) && !empty($extra_params) ) {
      $params = array_merge($params, $extra_params);
    }

    $post_string = '';
    foreach ($params as $key => $value) {
      $post_string .= $key . '[' . strlen(trim($value)) . ']=' . trim($value) . '&';
    }

    $post_string = substr($post_string, 0, -1);

    parse_str($PayPal->makeApiCall($api_url, $post_string), $response);

    if ( $response['RESULT'] != '0' ) {
      switch ( $response['RESULT'] ) {
        case '1':
        case '26':
          $error_message = $PayPal->getDef('module_ec_error_configuration');
          break;

        case '7':
          $error_message = $PayPal->getDef('module_ec_error_address');
          break;

        case '12':
          $error_message = $PayPal->getDef('module_ec_error_declined');
          break;

        case '1000':
          $error_message = $PayPal->getDef('module_ec_error_express_disabled');
          break;

        default:
          $error_message = $PayPal->getDef('module_ec_error_general');
          break;
      }

      $response['PHOENIX_ERROR_MESSAGE'] = $error_message;
    }

    return [
      'res' => $response,
      'success' => ($response['RESULT'] == '0'),
      'req' => $params,
    ];
  }
