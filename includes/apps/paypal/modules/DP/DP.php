<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_DP {

    public $_title;
    public $_short_title;
    public $_introduction;
    public $_req_notes;
    public $_pm_code = 'paypal_pro_dp';
    public $_pm_pf_code = 'paypal_pro_payflow_dp';
    public $_sort_order = 200;

    public function __construct() {
      global $PayPal;

      $this->_title = $PayPal->getDef('module_dp_title');
      $this->_short_title = $PayPal->getDef('module_dp_short_title');
      $this->_introduction = $PayPal->getDef('module_dp_introduction');

      $this->_req_notes = [];

      if ( !function_exists('curl_init') ) {
        $this->_req_notes[] = $PayPal->getDef('module_dp_error_curl');
      }

      if ( defined('PAYPAL_APP_GATEWAY') ) {
        if ( (PAYPAL_APP_GATEWAY == '1') && !$PayPal->hasCredentials('DP') ) { // PayPal
          $this->_req_notes[] = $PayPal->getDef('module_dp_error_credentials');
        } elseif ( (PAYPAL_APP_GATEWAY == '0') && !$PayPal->hasCredentials('DP', 'payflow') ) {
          $this->_req_notes[] = $PayPal->getDef('module_dp_error_credentials_payflow');
        }
      }

      if ( !$PayPal->isInstalled('EC') || !in_array(PAYPAL_APP_EC_STATUS, ['1', '0']) ) {
        $this->_req_notes[] = $PayPal->getDef('module_dp_error_express_module');
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

          if ( isset($sig[0], $sig[1], $sig[2]) && ($sig[0] === 'paypal') && ($sig[1] == $class) ) {
            return version_compare($sig[2], 4) >= 0;
          }
        }
      }

      return false;
    }

    public function migrate($PayPal) {
      $is_payflow = false;

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_SERVER') ) {
        $prefix = (MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_SERVER == 'Live') ? 'PAYPAL_APP_LIVE_' : 'PAYPAL_APP_SANDBOX_';

        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_DP_API_USERNAME', $prefix . 'API_USERNAME');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_DP_API_PASSWORD', $prefix . 'API_PASSWORD');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_DP_API_SIGNATURE', $prefix . 'API_SIGNATURE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_SERVER') ) {
        $is_payflow = true;

        $server = (MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_SERVER == 'Live') ? 'PAYPAL_APP_LIVE_PF_' : 'PAYPAL_APP_SANDBOX_PF_';

        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_USERNAME', $prefix . 'USER');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_PASSWORD', $prefix . 'PASSWORD');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_VENDOR', $prefix . 'VENDOR');
        $PayPal->migrate_parameter_if('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_PARTNER', $prefix . 'PARTNER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_METHOD') ) {
        $PayPal->saveParameter('PAYPAL_APP_DP_TRANSACTION_METHOD', (MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_METHOD == 'Sale') ? '1' : '0');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_METHOD');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_METHOD') ) {
        $PayPal->saveParameter('PAYPAL_APP_DP_TRANSACTION_METHOD', (MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_METHOD == 'Sale') ? '1' : '0');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_METHOD');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_ORDER_STATUS_ID') ) {
        $PayPal->saveParameter('PAYPAL_APP_DP_ORDER_STATUS_ID', MODULE_PAYMENT_PAYPAL_PRO_DP_ORDER_STATUS_ID);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_ORDER_STATUS_ID') ) {
        $PayPal->saveParameter('PAYPAL_APP_DP_ORDER_STATUS_ID', MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_ORDER_STATUS_ID);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_ZONE') ) {
        $PayPal->saveParameter('PAYPAL_APP_DP_ZONE', MODULE_PAYMENT_PAYPAL_PRO_DP_ZONE);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_ZONE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_ZONE') ) {
        $PayPal->saveParameter('PAYPAL_APP_DP_ZONE', MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_ZONE);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_ZONE');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_SORT_ORDER') ) {
        $PayPal->saveParameter('PAYPAL_APP_DP_SORT_ORDER', MODULE_PAYMENT_PAYPAL_PRO_DP_SORT_ORDER, 'Sort Order', 'Sort order of display (lowest to highest).');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_SORT_ORDER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_SORT_ORDER') ) {
        $PayPal->saveParameter('PAYPAL_APP_DP_SORT_ORDER', MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_SORT_ORDER, 'Sort Order', 'Sort order of display (lowest to highest).');
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_SORT_ORDER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTIONS_ORDER_STATUS_ID') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTIONS_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTIONS_ORDER_STATUS_ID') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTIONS_ORDER_STATUS_ID');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_STATUS') ) {
        $status = '-1';

        if ( (MODULE_PAYMENT_PAYPAL_PRO_DP_STATUS == 'True') && defined('MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_SERVER') ) {
          if ( MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_SERVER == 'Live' ) {
            $status = '1';
          } else {
            $status = '0';
          }
        }

        $PayPal->saveParameter('PAYPAL_APP_DP_STATUS', $status);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_STATUS');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_STATUS') ) {
        $status = '-1';

        if ( (MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_STATUS == 'True') && defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_SERVER') ) {
          if ( MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_SERVER == 'Live' ) {
            $status = '1';
          } else {
            $status = '0';
          }
        }

        $PayPal->saveParameter('PAYPAL_APP_DP_STATUS', $status);
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_STATUS');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_SERVER') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_TRANSACTION_SERVER');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_SERVER') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_TRANSACTION_SERVER');
      }

      $cards = [
        'MODULE_PAYMENT_PAYPAL_PRO_DP_CARDTYPE_VISA',
        'MODULE_PAYMENT_PAYPAL_PRO_DP_CARDTYPE_MASTERCARD',
        'MODULE_PAYMENT_PAYPAL_PRO_DP_CARDTYPE_DISCOVER',
        'MODULE_PAYMENT_PAYPAL_PRO_DP_CARDTYPE_AMEX',
        'MODULE_PAYMENT_PAYPAL_PRO_DP_CARDTYPE_MAESTRO',
      ];

      $cards_pass = true;

      foreach ( $cards as $c ) {
        if ( !defined($c) ) {
          $cards_pass = false;
          break;
        }
      }

      if ( $cards_pass === true ) {
        $cards_installed = [];

        foreach ( $cards as $c ) {
          if ( constant($c) === 'True' ) {
            $cards_installed[] = strtolower(substr($c, strrpos($c, '_')+1));
          }
        }

        $PayPal->saveParameter('PAYPAL_APP_DP_CARDS', implode(';', $cards_installed));
      }

      foreach ( $cards as $c ) {
        $PayPal->deleteParameter($c);
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_VERIFY_SSL') ) {
        if ( !defined('PAYPAL_APP_VERIFY_SSL') ) {
          $PayPal->saveParameter('PAYPAL_APP_VERIFY_SSL', (MODULE_PAYMENT_PAYPAL_PRO_DP_VERIFY_SSL == 'True') ? '1' : '0');
        }

        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_VERIFY_SSL');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_VERIFY_SSL') ) {
        if ( !defined('PAYPAL_APP_VERIFY_SSL') ) {
          $PayPal->saveParameter('PAYPAL_APP_VERIFY_SSL', (MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_VERIFY_SSL == 'True') ? '1' : '0');
        }

        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_VERIFY_SSL');
      }

      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_PRO_DP_PROXY', 'PAYPAL_APP_PROXY');
      $PayPal->migrate_parameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_PROXY', 'PAYPAL_APP_PROXY');

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_DP_DEBUG_EMAIL') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_DP_DEBUG_EMAIL');
      }

      if ( defined('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_DEBUG_EMAIL') ) {
        $PayPal->deleteParameter('MODULE_PAYMENT_PAYPAL_PRO_PAYFLOW_DP_DEBUG_EMAIL');
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
