<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  $GLOBALS['db']->query('DELETE FROM paypal_app_log');

  $PayPal->addAlert($PayPal->getDef('alert_delete_success'), 'success');

  Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'log']));
