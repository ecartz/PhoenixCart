<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_Cfg_log_transactions {

    public $default = '1';
    public $sort_order = 500;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_log_transactions_title');
      $this->description = $PayPal->getDef('cfg_log_transactions_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="logTransactionsSelectionAll" name="log_transactions" value="1"' . (PAYPAL_APP_LOG_TRANSACTIONS == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="logTransactionsSelectionAll">' . $PayPal->getDef('cfg_log_transactions_all') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="logTransactionsSelectionErrors" name="log_transactions" value="0"' . (PAYPAL_APP_LOG_TRANSACTIONS == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="logTransactionsSelectionErrors">' . $PayPal->getDef('cfg_log_transactions_errors') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="logTransactionsSelectionDisabled" name="log_transactions" value="-1"' . (PAYPAL_APP_LOG_TRANSACTIONS == '-1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="logTransactionsSelectionDisabled">' . $PayPal->getDef('cfg_log_transactions_disabled') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div id="logSelection">{$input}</div>
EOHTML;
    }

  }
