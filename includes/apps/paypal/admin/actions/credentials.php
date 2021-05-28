<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  $content = 'credentials.php';

  $current_module = isset($_GET['module']) && ('PF' === $_GET['module']) ? 'PF' : 'PP';

  $data = [
    'PAYPAL_APP_LIVE_SELLER_EMAIL',
    'PAYPAL_APP_LIVE_SELLER_EMAIL_PRIMARY',
    'PAYPAL_APP_LIVE_API_USERNAME',
    'PAYPAL_APP_LIVE_API_PASSWORD',
    'PAYPAL_APP_LIVE_API_SIGNATURE',
    'PAYPAL_APP_LIVE_MERCHANT_ID',
    'PAYPAL_APP_SANDBOX_SELLER_EMAIL',
    'PAYPAL_APP_SANDBOX_SELLER_EMAIL_PRIMARY',
    'PAYPAL_APP_SANDBOX_API_USERNAME',
    'PAYPAL_APP_SANDBOX_API_PASSWORD',
    'PAYPAL_APP_SANDBOX_API_SIGNATURE',
    'PAYPAL_APP_SANDBOX_MERCHANT_ID',
    'PAYPAL_APP_LIVE_PF_PARTNER',
    'PAYPAL_APP_LIVE_PF_VENDOR',
    'PAYPAL_APP_LIVE_PF_USER',
    'PAYPAL_APP_LIVE_PF_PASSWORD',
    'PAYPAL_APP_SANDBOX_PF_PARTNER',
    'PAYPAL_APP_SANDBOX_PF_VENDOR',
    'PAYPAL_APP_SANDBOX_PF_USER',
    'PAYPAL_APP_SANDBOX_PF_PASSWORD',
  ];

  foreach ( $data as $key ) {
    if ( !defined($key) ) {
      $PayPal->saveParameter($key, '');
    }
  }
