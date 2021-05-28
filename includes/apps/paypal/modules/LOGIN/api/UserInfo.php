<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  function PayPal_LOGIN_Api_UserInfo($PayPal, $server, $params) {
    if ( $server == 'live' ) {
      $api_url = 'https://api.paypal.com/v1/identity/openidconnect/userinfo/?schema=openid&access_token=' . $params['access_token'];
    } else {
      $api_url = 'https://api.sandbox.paypal.com/v1/identity/openidconnect/userinfo/?schema=openid&access_token=' . $params['access_token'];
    }

    $response = json_decode($PayPal->makeApiCall($api_url), true);

    return [
      'res' => $response,
      'success' => (is_array($response) && !isset($response['error'])),
      'req' => $params,
    ];
  }
