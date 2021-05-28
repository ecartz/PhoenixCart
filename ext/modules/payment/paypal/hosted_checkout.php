<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  chdir('../../../../');
  require 'includes/application_top.php';

  $error = false;

  if ( !defined('PAYPAL_APP_HS_STATUS') || !in_array(PAYPAL_APP_HS_STATUS, ['1', '0']) ) {
    $error = true;
  }

  if ( $error === false ) {
    if ( !isset($_GET['key'], $_SESSION['pphs_key'], $_SESSION['pphs_result']) || ($_GET['key'] !== $_SESSION['pphs_key']) ) {
      $error = true;
    }
  }

  if ( $error === false ) {
    if (($_SESSION['pphs_result']['ACK'] != 'Success') && ($_SESSION['pphs_result']['ACK'] != 'SuccessWithWarning')) {
      $error = true;

      $_SESSION['pphs_error_msg'] = $_SESSION['pphs_result']['L_LONGMESSAGE0'];
    }
  }

  if ( $error === false ) {
    if ( PAYPAL_APP_HS_STATUS == '1' ) {
      $form_url = 'https://securepayments.paypal.com/webapps/HostedSoleSolutionApp/webflow/sparta/hostedSoleSolutionProcess';
    } else {
      $form_url = 'https://securepayments.sandbox.paypal.com/webapps/HostedSoleSolutionApp/webflow/sparta/hostedSoleSolutionProcess';
    }
  } else {
    $form_url = $Linker->build('checkout_payment.php', ['payment_error' => 'paypal_pro_hs']);
  }

  require $Template->map(__FILE__, 'ext');
  require DIR_FS_CATALOG . 'includes/application_bottom.php';
