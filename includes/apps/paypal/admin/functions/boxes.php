<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  function app_paypal_get_admin_box_links() {
    $paypal_menu = [[
      'code' => 'paypal.php',
      'title' => MODULES_ADMIN_MENU_PAYPAL_START,
      'link' => Guarantor::ensure_global('Admin')->link('paypal.php'),
    ]];

    $paypal_menu_check = [
      'PAYPAL_APP_LIVE_SELLER_EMAIL',
      'PAYPAL_APP_LIVE_API_USERNAME',
      'PAYPAL_APP_SANDBOX_SELLER_EMAIL',
      'PAYPAL_APP_SANDBOX_API_USERNAME',
      'PAYPAL_APP_LIVE_PF_VENDOR',
      'PAYPAL_APP_SANDBOX_PF_VENDOR',
    ];

    foreach ( $paypal_menu_check as $value ) {
      if ( defined($value) && !Text::is_empty(constant($value)) ) {
        $paypal_menu = [
          [
            'code' => 'paypal.php',
            'title' => MODULES_ADMIN_MENU_PAYPAL_BALANCE,
            'link' => $GLOBALS['Admin']->link('paypal.php', ['action' => 'balance']),
          ],
          [
            'code' => 'paypal.php',
            'title' => MODULES_ADMIN_MENU_PAYPAL_CONFIGURE,
            'link' => $GLOBALS['Admin']->link('paypal.php', ['action' => 'configure']),
          ],
          [
            'code' => 'paypal.php',
            'title' => MODULES_ADMIN_MENU_PAYPAL_MANAGE_CREDENTIALS,
            'link' => $GLOBALS['Admin']->link('paypal.php', ['action' => 'credentials']),
          ],
          [
            'code' => 'paypal.php',
            'title' => MODULES_ADMIN_MENU_PAYPAL_LOG,
            'link' => $GLOBALS['Admin']->link('paypal.php', ['action' => 'log']),
          ],
        ];

        break;
      }
    }

    return $paypal_menu;
  }
