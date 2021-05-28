<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  $PayPal->install($current_module);
  $PayPal->addAlert($PayPal->getDef('alert_module_install_success'), 'success');

  Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'configure', 'module' => $current_module]));
