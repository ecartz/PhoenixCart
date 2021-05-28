<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_PS {

    public $_title;
    public $_short_title;
    public $_introduction;
    public $_req_notes = [];
    public $_pm_code = 'paypal_standard';
    public $_sort_order = 400;

    public function __construct() {
      global $PayPal;

      $this->_title = $PayPal->getDef('module_ps_title');
      $this->_short_title = $PayPal->getDef('module_ps_short_title');
      $this->_introduction = $PayPal->getDef('module_ps_introduction');

      if ( !function_exists('curl_init') ) {
        $this->_req_notes[] = $PayPal->getDef('module_ps_error_curl');
      }

      if ( !$PayPal->hasCredentials('PS', 'email') ) {
        $this->_req_notes[] = $PayPal->getDef('module_ps_error_credentials');
      }

      if ( !defined('PAYPAL_APP_PS_PDT_IDENTITY_TOKEN') || (Text::is_empty(PAYPAL_APP_PS_PDT_IDENTITY_TOKEN) && !$PayPal->hasCredentials('PS')) ) {
        $this->_req_notes[] = $PayPal->getDef('module_ps_error_credentials_pdt_api');
      }

      $this->_req_notes[] = $PayPal->getDef('module_ps_info_auto_return_url', [
        'auto_return_url' => Guarantor::ensure_global('Admin')->catalog('checkout_process.php')
      ]);
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

      if ( file_exists(DIR_FS_CATALOG . 'includes/modules/payment/' . $class . '.php') ) {
        if ( !class_exists($class) ) {
          require DIR_FS_CATALOG . 'includes/modules/payment/' . $class . '.php';
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
      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_GATEWAY_SERVER') ) {
        $prefix = (MODULE_PAYMENT_PAYPAL_STANDARD_GATEWAY_SERVER == 'Live') ? 'PAYPAL_APP_LIVE' : 'PAYPAL_APP_SANDBOX';

        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_STANDARD_ID', $prefix . 'SELLER_EMAIL');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_STANDARD_PRIMARY_ID', $prefix . 'SELLER_EMAIL_PRIMARY');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_PAGE_STYLE') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_PAGE_STYLE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_TRANSACTION_METHOD') ) {
        $PayPal->saveParameter('PAYPAL_APP_PS_TRANSACTION_METHOD', (MODULE_PAYMENT_PAYPAL_STANDARD_TRANSACTION_METHOD == 'Sale') ? '1' : '0');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_TRANSACTION_METHOD');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_PREPARE_ORDER_STATUS_ID') ) {
        $PayPal->saveParameter('PAYPAL_APP_PS_PREPARE_ORDER_STATUS_ID', MODULE_PAYMENT_PAYPAL_STANDARD_PREPARE_ORDER_STATUS_ID);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_PREPARE_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_ORDER_STATUS_ID') ) {
        $PayPal->saveParameter('PAYPAL_APP_PS_ORDER_STATUS_ID', MODULE_PAYMENT_PAYPAL_STANDARD_ORDER_STATUS_ID);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_ZONE') ) {
        $PayPal->saveParameter('PAYPAL_APP_PS_ZONE', MODULE_PAYMENT_PAYPAL_STANDARD_ZONE);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_ZONE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_SORT_ORDER') ) {
        $PayPal->saveParameter('PAYPAL_APP_PS_SORT_ORDER', MODULE_PAYMENT_PAYPAL_STANDARD_SORT_ORDER, 'Sort Order', 'Sort order of display (lowest to highest).');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_SORT_ORDER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_TRANSACTIONS_ORDER_STATUS_ID') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_TRANSACTIONS_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_STATUS') ) {
        $status = '-1';

        if ( (MODULE_PAYMENT_PAYPAL_STANDARD_STATUS == 'True') && defined('MODULE_PAYMENT_PAYPAL_STANDARD_GATEWAY_SERVER') ) {
          if ( MODULE_PAYMENT_PAYPAL_STANDARD_GATEWAY_SERVER == 'Live' ) {
            $status = '1';
          } else {
            $status = '0';
          }
        }

        $PayPal->saveParameter('PAYPAL_APP_PS_STATUS', $status);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_STATUS');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_GATEWAY_SERVER') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_GATEWAY_SERVER');
      }

      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_VERIFY_SSL', 'PAYPAL_APP_VERIFY_SSL');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_PROXY', 'PAYPAL_APP_PROXY');

      if ( defined('MODULE_PAYMENT_PAYPAL_STANDARD_DEBUG_EMAIL') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_STANDARD_DEBUG_EMAIL');
      }

      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_EWP_STATUS', 'PAYPAL_APP_PS_EWP_STATUS');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_EWP_PRIVATE_KEY', 'PAYPAL_APP_PS_EWP_PRIVATE_KEY');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_EWP_PUBLIC_KEY', 'PAYPAL_APP_PS_EWP_PUBLIC_CERT');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_EWP_CERT_ID', 'PAYPAL_APP_PS_EWP_PUBLIC_CERT_ID');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_EWP_PAYPAL_KEY', 'PAYPAL_APP_PS_EWP_PAYPAL_CERT');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_EWP_WORKING_DIRECTORY', 'PAYPAL_APP_PS_EWP_WORKING_DIRECTORY');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_STANDARD_EWP_OPENSSL', 'PAYPAL_APP_PS_EWP_OPENSSL');
    }

  }
