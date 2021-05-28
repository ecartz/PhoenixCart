<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  $data = ( $current_module == 'PP' ) ? [
    'PAYPAL_APP_LIVE_SELLER_EMAIL' => Text::input($_POST['live_email'] ?? ''),
    'PAYPAL_APP_LIVE_SELLER_EMAIL_PRIMARY' => Text::input($_POST['live_email_primary'] ?? ''),
    'PAYPAL_APP_LIVE_MERCHANT_ID' => Text::input($_POST['live_merchant_id'] ?? ''),
    'PAYPAL_APP_LIVE_API_USERNAME' => Text::input($_POST['live_username'] ?? ''),
    'PAYPAL_APP_LIVE_API_PASSWORD' => Text::input($_POST['live_password'] ?? ''),
    'PAYPAL_APP_LIVE_API_SIGNATURE' => Text::input($_POST['live_signature'] ?? ''),
    'PAYPAL_APP_SANDBOX_SELLER_EMAIL' => Text::input($_POST['sandbox_email'] ?? ''),
    'PAYPAL_APP_SANDBOX_SELLER_EMAIL_PRIMARY' => Text::input($_POST['sandbox_email_primary'] ?? ''),
    'PAYPAL_APP_SANDBOX_MERCHANT_ID' => Text::input($_POST['sandbox_merchant_id'] ?? ''),
    'PAYPAL_APP_SANDBOX_API_USERNAME' => Text::input($_POST['sandbox_username'] ?? ''),
    'PAYPAL_APP_SANDBOX_API_PASSWORD' => Text::input($_POST['sandbox_password'] ?? ''),
    'PAYPAL_APP_SANDBOX_API_SIGNATURE' => Text::input($_POST['sandbox_signature'] ?? ''),
  ] : [
    'PAYPAL_APP_LIVE_PF_PARTNER' => Text::input($_POST['live_partner'] ?? ''),
    'PAYPAL_APP_LIVE_PF_VENDOR' => Text::input($_POST['live_vendor'] ?? ''),
    'PAYPAL_APP_LIVE_PF_USER' => Text::input($_POST['live_user'] ?? ''),
    'PAYPAL_APP_LIVE_PF_PASSWORD' => Text::input($_POST['live_password'] ?? ''),
    'PAYPAL_APP_SANDBOX_PF_PARTNER' => Text::input($_POST['sandbox_partner'] ?? ''),
    'PAYPAL_APP_SANDBOX_PF_VENDOR' => Text::input($_POST['sandbox_vendor'] ?? ''),
    'PAYPAL_APP_SANDBOX_PF_USER' => Text::input($_POST['sandbox_user'] ?? ''),
    'PAYPAL_APP_SANDBOX_PF_PASSWORD' => Text::input($_POST['sandbox_password'] ?? ''),
  ];

  foreach ( $data as $key => $value ) {
    $PayPal->saveParameter($key, $value);
  }

  $PayPal->addAlert($PayPal->getDef('alert_credentials_saved_success'), 'success');

  Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'credentials', 'module' => $current_module]));
