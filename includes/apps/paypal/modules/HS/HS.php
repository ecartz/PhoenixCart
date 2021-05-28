<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_HS {

    public $_title;
    public $_short_title;
    public $_introduction;
    public $_req_notes;
    public $_pm_code = 'paypal_pro_hs';
    public $_sort_order = 300;

    public function __construct() {
      global $PayPal;

      $this->_title = $PayPal->getDef('module_hs_title');
      $this->_short_title = $PayPal->getDef('module_hs_short_title');
      $this->_introduction = $PayPal->getDef('module_hs_introduction');

      $this->_req_notes = [];

      if ( !function_exists('curl_init') ) {
        $this->_req_notes[] = $PayPal->getDef('module_hs_error_curl');
      }

      if ( defined('PAYPAL_APP_GATEWAY') ) {
        if ( (PAYPAL_APP_GATEWAY == '1') && !$PayPal->hasCredentials('HS') ) { // PayPal
          $this->_req_notes[] = $PayPal->getDef('module_hs_error_credentials');
        } elseif ( PAYPAL_APP_GATEWAY == '0' ) { // Payflow
          $this->_req_notes[] = $PayPal->getDef('module_hs_error_payflow');
        }
      }
    }

    public function getTitle() {
      return $this->_title;
    }

    public function getShortTitle() {
      return $this->_short_title;
    }

    public function install($PayPal) {
      $installed = explode(';', MODULE_PAYMENT_INSTALLED);
      $installed[] = $this->_pm_code . '.php';

      $PayPal->saveParameter('MODULE_PAYMENT_INSTALLED', implode(';', $installed));
    }

    public function uninstall($PayPal) {
      $installed = explode(';', MODULE_PAYMENT_INSTALLED);
      $installed_pos = array_search($this->_pm_code . '.php', $installed);

      if ( $installed_pos !== false ) {
        unset($installed[$installed_pos]);

        $PayPal->saveParameter('MODULE_PAYMENT_INSTALLED', implode(';', $installed));
      }
    }

    public function canMigrate() {
      $class = $this->_pm_code;

      $file = DIR_FS_CATALOG . "includes/modules/payment/$class.php";
      if ( file_exists($file) ) {
        if ( !class_exists($class) ) {
          require $file;
        }

        $module = new $class();

        if ( isset($module->signature) ) {
          $sig = explode('|', $module->signature);

          if ( isset($sig[0], $sig[1], $sig[2]) && ($sig[0] == 'paypal') && ($sig[1] == $class) ) {
            return version_compare($sig[2], 4) >= 0;
          }
        }
      }

      return false;
    }

    public function migrate($PayPal) {
      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER') ) {
        $prefix = (MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER == 'Live') ? 'PAYPAL_APP_LIVE_' : 'PAYPAL_APP_SANDBOX_';

        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_HS_ID', $prefix . 'SELLER_EMAIL');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_HS_PRIMARY_ID', $prefix . 'SELLER_EMAIL_PRIMARY');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_HS_API_USERNAME', $prefix . 'API_USERNAME');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_HS_API_PASSWORD', $prefix . 'API_PASSWORD');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_HS_API_SIGNATURE', $prefix . 'API_SIGNATURE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTION_METHOD') ) {
        $PayPal->saveParameter('PAYPAL_APP_HS_TRANSACTION_METHOD', (MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTION_METHOD == 'Sale') ? '1' : '0');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTION_METHOD');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_PREPARE_ORDER_STATUS_ID') ) {
        $PayPal->saveParameter('PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID', MODULE_PAYMENT_PAYPAL_PRO_HS_PREPARE_ORDER_STATUS_ID);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_PREPARE_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID') ) {
        $PayPal->saveParameter('PAYPAL_APP_HS_ORDER_STATUS_ID', MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_ZONE') ) {
        $PayPal->saveParameter('PAYPAL_APP_HS_ZONE', MODULE_PAYMENT_PAYPAL_PRO_HS_ZONE);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_ZONE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_SORT_ORDER') ) {
        $PayPal->saveParameter('PAYPAL_APP_HS_SORT_ORDER', MODULE_PAYMENT_PAYPAL_PRO_HS_SORT_ORDER, 'Sort Order', 'Sort order of display (lowest to highest).');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_SORT_ORDER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTIONS_ORDER_STATUS_ID') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTIONS_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_STATUS') ) {
        $status = '-1';

        if ( (MODULE_PAYMENT_PAYPAL_PRO_HS_STATUS == 'True') && defined('MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER') ) {
          if ( MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER == 'Live' ) {
            $status = '1';
          } else {
            $status = '0';
          }
        }

        $PayPal->saveParameter('PAYPAL_APP_HS_STATUS', $status);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_STATUS');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_VERIFY_SSL') ) {
        if ( !defined('PAYPAL_APP_VERIFY_SSL') ) {
          $PayPal->saveParameter('PAYPAL_APP_VERIFY_SSL', (MODULE_PAYMENT_PAYPAL_PRO_HS_VERIFY_SSL == 'True') ? '1' : '0');
        }

        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_VERIFY_SSL');
      }

      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_PRO_HS_PROXY', 'PAYPAL_APP_PROXY');

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_HS_DEBUG_EMAIL') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_HS_DEBUG_EMAIL');
      }
    }

  }
