<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC {

    public $_title;
    public $_short_title;
    public $_introduction;
    public $_req_notes = [];
    public $_pm_code = 'paypal_express';
    public $_pm_pf_code = 'paypal_pro_payflow_ec';
    public $_sort_order = 100;

    public function __construct() {
      global $PayPal;

      $this->_title = $PayPal->getDef('module_ec_title');
      $this->_short_title = $PayPal->getDef('module_ec_short_title');
      $this->_introduction = $PayPal->getDef('module_ec_introduction');

      if ( !function_exists('curl_init') ) {
        $this->_req_notes[] = $PayPal->getDef('module_ec_error_curl');
      }

      if ( defined('PAYPAL_APP_GATEWAY') ) {
        if ( (PAYPAL_APP_GATEWAY == '1') && !$PayPal->hasCredentials('EC') ) {
// PayPal
          $this->_req_notes[] = $PayPal->getDef('module_ec_error_credentials');
        } elseif ( (PAYPAL_APP_GATEWAY == '0') && !$PayPal->hasCredentials('EC', 'payflow') ) {
          $this->_req_notes[] = $PayPal->getDef('module_ec_error_credentials_payflow');
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
      return $this->doMigrationCheck($this->_pm_code) || $this->doMigrationCheck($this->_pm_pf_code);
    }

    public function doMigrationCheck($class) {
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
      $is_payflow = false;

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER') ) {
        $prefix = (MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live') ? 'PAYPAL_APP_LIVE_' : 'PAYPAL_APP_SANDBOX_';

        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_EXPRESS_SELLER_ACCOUNT', $prefix . 'SELLER_EMAIL');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME', $prefix . 'API_USERNAME');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_EXPRESS_API_PASSWORD', $prefix . 'API_PASSWORD');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_EXPRESS_API_SIGNATURE', $prefix . 'API_SIGNATURE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_SERVER') ) {
        $is_payflow = true;

        $prefix = (MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_SERVER == 'Live') ? 'PAYPAL_APP_LIVE_PF_' : 'PAYPAL_APP_SANDBOX_PF_';

        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_USERNAME', $prefix . 'USER');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_PASSWORD', $prefix . 'PASSWORD');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_VENDOR', $prefix . 'VENDOR');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_PARTNER', $prefix . 'PARTNER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_ACCOUNT_OPTIONAL') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_ACCOUNT_OPTIONAL', (MODULE_PAYMENT_PAYPAL_EXPRESS_ACCOUNT_OPTIONAL == 'True') ? '1' : '0');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_ACCOUNT_OPTIONAL');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_INSTANT_UPDATE') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_INSTANT_UPDATE', (MODULE_PAYMENT_PAYPAL_EXPRESS_INSTANT_UPDATE == 'True') ? '1' : '0');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_INSTANT_UPDATE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_CHECKOUT_IMAGE') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_CHECKOUT_IMAGE', (MODULE_PAYMENT_PAYPAL_EXPRESS_CHECKOUT_IMAGE == 'Static') ? '0' : '1');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_CHECKOUT_IMAGE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_PAGE_STYLE') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_PAGE_STYLE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_PAGE_STYLE') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_PAGE_STYLE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_METHOD') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_TRANSACTION_METHOD', (MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_METHOD == 'Sale') ? '1' : '0');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_METHOD');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_METHOD') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_TRANSACTION_METHOD', (MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_METHOD == 'Sale') ? '1' : '0');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_METHOD');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_ORDER_STATUS_ID', MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_ORDER_STATUS_ID') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_ORDER_STATUS_ID', MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_ORDER_STATUS_ID);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_ZONE') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_ZONE', MODULE_PAYMENT_PAYPAL_EXPRESS_ZONE);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_ZONE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_ZONE') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_ZONE', MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_ZONE);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_ZONE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_SORT_ORDER') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_SORT_ORDER', MODULE_PAYMENT_PAYPAL_EXPRESS_SORT_ORDER, 'Sort Order', 'Sort order of display (lowest to highest).');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_SORT_ORDER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_SORT_ORDER') ) {
        $PayPal->saveParameter('PAYPAL_APP_EC_SORT_ORDER', MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_SORT_ORDER, 'Sort Order', 'Sort order of display (lowest to highest).');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_SORT_ORDER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTIONS_ORDER_STATUS_ID') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTIONS_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTIONS_ORDER_STATUS_ID') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTIONS_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_STATUS') ) {
        $status = '-1';

        if ( (MODULE_PAYMENT_PAYPAL_EXPRESS_STATUS == 'True') && defined('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER') ) {
          if ( MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live' ) {
            $status = '1';
          } else {
            $status = '0';
          }
        }

        $PayPal->saveParameter('PAYPAL_APP_EC_STATUS', $status);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_STATUS');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_STATUS') ) {
        $status = '-1';

        if ( (MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_STATUS == 'True') && defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_SERVER') ) {
          if ( MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_SERVER == 'Live' ) {
            $status = '1';
          } else {
            $status = '0';
          }
        }

        $PayPal->saveParameter('PAYPAL_APP_EC_STATUS', $status);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_STATUS');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_SERVER') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_TRANSACTION_SERVER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_VERIFY_SSL') ) {
        if ( !defined('PAYPAL_APP_VERIFY_SSL') ) {
          $PayPal->saveParameter('PAYPAL_APP_VERIFY_SSL', (MODULE_PAYMENT_PAYPAL_EXPRESS_VERIFY_SSL == 'True') ? '1' : '0');
        }

        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_VERIFY_SSL');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_VERIFY_SSL') ) {
        if ( !defined('PAYPAL_APP_VERIFY_SSL') ) {
          $PayPal->saveParameter('PAYPAL_APP_VERIFY_SSL', (MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_VERIFY_SSL == 'True') ? '1' : '0');
        }

        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_VERIFY_SSL');
      }

      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_EXPRESS_PROXY', 'PAYPAL_APP_PROXY');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_PROXY', 'PAYPAL_APP_PROXY');

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_DEBUG_EMAIL') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_DEBUG_EMAIL');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_DEBUG_EMAIL') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_EC_DEBUG_EMAIL');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_CHECKOUT_FLOW') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_CHECKOUT_FLOW');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_DISABLE_IE_COMPAT') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_EXPRESS_DISABLE_IE_COMPAT');
      }

      if ( $is_payflow === true ) {
        $installed = explode(';', MODULE_PAYMENT_INSTALLED);
        $installed_pos = array_search($this->_pm_pf_code . '.php', $installed);

        if ( $installed_pos !== false ) {
          unset($installed[$installed_pos]);

          $PayPal->saveParameter('MODULE_PAYMENT_INSTALLED', implode(';', $installed));
        }
      }
    }

  }
