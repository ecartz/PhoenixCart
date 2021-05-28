<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  if ( $current_module === 'G' ) {
    $cut = 'PAYPAL_APP_';
  } else {
    $cut = 'PAYPAL_APP_' . $current_module . '_';
  }

  $cut_length = strlen($cut);

  foreach ( $PayPal->getParameters($current_module) as $key ) {
    $p = strtolower(substr($key, $cut_length));

    if ( isset($_POST[$p]) ) {
      $PayPal->saveParameter($key, $_POST[$p]);
    }
  }

  $PayPal->addAlert($PayPal->getDef('alert_cfg_saved_success'), 'success');

  Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'configure', 'module' => $current_module]));
