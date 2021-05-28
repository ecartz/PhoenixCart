<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_LOGIN {

    public $_title;
    public $_short_title;
    public $_introduction;
    public $_req_notes;
    public $_cm_code = 'login/cm_paypal_login';
    public $_sort_order = 1000;

    public function __construct() {
      global $PayPal;

      $this->_title = $PayPal->getDef('module_login_title');
      $this->_short_title = $PayPal->getDef('module_login_short_title');
      $this->_introduction = $PayPal->getDef('module_login_introduction');

      $this->_req_notes = [];

      if ( !function_exists('curl_init') ) {
        $this->_req_notes[] = $PayPal->getDef('module_login_error_curl');
      }

      if ( defined('PAYPAL_APP_LOGIN_STATUS') ) {
        if ( ((PAYPAL_APP_LOGIN_STATUS == '1') && (Text::is_empty(PAYPAL_APP_LOGIN_LIVE_CLIENT_ID) || Text::is_empty(PAYPAL_APP_LOGIN_LIVE_SECRET)))
          || ((PAYPAL_APP_LOGIN_STATUS == '0') && (Text::is_empty(PAYPAL_APP_LOGIN_SANDBOX_CLIENT_ID) || Text::is_empty(PAYPAL_APP_LOGIN_SANDBOX_SECRET))) )
        {
          $this->_req_notes[] = $PayPal->getDef('module_login_error_credentials');
        }

        $this->_req_notes[] = $PayPal->getDef('module_login_notice_paypal_app_return_url', [
          'return_url' => Guarantor::ensure_global('Admin')->catalog('login.php', ['action' => 'paypal_login'])
        ]);
      }
    }

    public function getTitle() {
      return $this->_title;
    }

    public function getShortTitle() {
      return $this->_short_title;
    }

    public function install($PayPal) {
      $installed = explode(';', MODULE_CONTENT_INSTALLED);
      $installed[] = $this->_cm_code;

      $PayPal->saveParameter('MODULE_CONTENT_INSTALLED', implode(';', $installed));
    }

    public function uninstall($PayPal) {
      $installed = explode(';', MODULE_CONTENT_INSTALLED);
      $installed_pos = array_search($this->_cm_code, $installed);

      if ( $installed_pos !== false ) {
        unset($installed[$installed_pos]);

        $PayPal->saveParameter('MODULE_CONTENT_INSTALLED', implode(';', $installed));
      }
    }

    public function canMigrate() {
      $class = basename($this->_cm_code);

      $file = DIR_FS_CATALOG . 'includes/modules/content/' . $this->_cm_code . '.php';
      if ( file_exists($file) ) {
        if ( !class_exists($class) ) {
          require $file;
        }

        $module = new $class();

        if ( isset($module->signature) ) {
          $sig = explode('|', $module->signature);

          if ( isset($sig[0], $sig[1], $sig[2]) && ($sig[0] == 'paypal') && ($sig[1] == 'paypal_login') ) {
            return version_compare($sig[2], 4) >= 0;
          }
        }
      }

      return false;
    }

    public function migrate($PayPal) {
      if ( defined('MODULE_CONTENT_PAYPAL_LOGIN_SERVER_TYPE') ) {
        $prefix = (MODULE_CONTENT_PAYPAL_LOGIN_SERVER_TYPE == 'Live') ? 'PAYPAL_APP_LOGIN_LIVE_' : 'PAYPAL_APP_LOGIN_SANDBOX_';

        $PayPal->migrate_parameter_if('MODULE_CONTENT_PAYPAL_LOGIN_CLIENT_ID', $prefix . 'CLIENT_ID');
        $PayPal->migrate_parameter_if('MODULE_CONTENT_PAYPAL_LOGIN_SECRET', $prefix . 'SECRET');
      }

      if ( defined('MODULE_CONTENT_PAYPAL_LOGIN_THEME') ) {
        $PayPal->saveParameter('PAYPAL_APP_LOGIN_THEME', MODULE_CONTENT_PAYPAL_LOGIN_THEME);
        $PayPal->deleteParameter('MODULE_CONTENT_PAYPAL_LOGIN_THEME');
      }

      if ( defined('MODULE_CONTENT_PAYPAL_LOGIN_ATTRIBUTES') ) {
        $PayPal->saveParameter('PAYPAL_APP_LOGIN_ATTRIBUTES', MODULE_CONTENT_PAYPAL_LOGIN_ATTRIBUTES);
        $PayPal->deleteParameter('MODULE_CONTENT_PAYPAL_LOGIN_ATTRIBUTES');
      }

      if ( defined('MODULE_CONTENT_PAYPAL_LOGIN_CONTENT_WIDTH') ) {
        $PayPal->saveParameter('PAYPAL_APP_LOGIN_CONTENT_WIDTH', MODULE_CONTENT_PAYPAL_LOGIN_CONTENT_WIDTH, 'Content Width', 'Should the content be shown in a full or half width container?', 'Config::select_one([\'Full\', \'Half\'], ');
        $PayPal->deleteParameter('MODULE_CONTENT_PAYPAL_LOGIN_CONTENT_WIDTH');
      }

      if ( defined('MODULE_CONTENT_PAYPAL_LOGIN_SORT_ORDER') ) {
        $PayPal->saveParameter('PAYPAL_APP_LOGIN_SORT_ORDER', MODULE_CONTENT_PAYPAL_LOGIN_SORT_ORDER, 'Sort Order', 'Sort order of display (lowest to highest).');
        $PayPal->deleteParameter('MODULE_CONTENT_PAYPAL_LOGIN_SORT_ORDER');
      }

      if ( defined('MODULE_CONTENT_PAYPAL_LOGIN_STATUS') ) {
        $status = '-1';

        if ( (MODULE_CONTENT_PAYPAL_LOGIN_STATUS == 'True') && defined('MODULE_CONTENT_PAYPAL_LOGIN_SERVER_TYPE') ) {
          if ( MODULE_CONTENT_PAYPAL_LOGIN_SERVER_TYPE == 'Live' ) {
            $status = '1';
          } else {
            $status = '0';
          }
        }

        $PayPal->saveParameter('PAYPAL_APP_LOGIN_STATUS', $status);
        $PayPal->deleteParameter('MODULE_CONTENT_PAYPAL_LOGIN_STATUS');
      }

      if ( defined('MODULE_CONTENT_PAYPAL_LOGIN_SERVER_TYPE') ) {
        $PayPal->deleteParameter('MODULE_CONTENT_PAYPAL_LOGIN_SERVER_TYPE');
      }

      if ( defined('MODULE_CONTENT_PAYPAL_LOGIN_VERIFY_SSL') ) {
        if ( !defined('PAYPAL_APP_VERIFY_SSL') ) {
          $PayPal->saveParameter('PAYPAL_APP_VERIFY_SSL', (MODULE_CONTENT_PAYPAL_LOGIN_VERIFY_SSL == 'True') ? '1' : '0');
        }

        $PayPal->deleteParameter('MODULE_CONTENT_PAYPAL_LOGIN_VERIFY_SSL');
      }

      $PayPal->migrate_parameter('MMODULE_CONTENT_PAYPAL_LOGIN_PROXY', 'PAYPAL_APP_PROXY');
    }

  }
