<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_transaction_method {

    public $default = '1';
    public $title;
    public $description;
    public $sort_order = 700;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_transaction_method_title');
      $this->description = $PayPal->getDef('cfg_ec_transaction_method_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="transactionMethodSelectionSale" name="transaction_method" value="1"' . (PAYPAL_APP_EC_TRANSACTION_METHOD == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="transactionMethodSelectionSale">' . $PayPal->getDef('cfg_ec_transaction_method_sale') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="transactionMethodSelectionAuthorize" name="transaction_method" value="0"' . (PAYPAL_APP_EC_TRANSACTION_METHOD == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="transactionMethodSelectionAuthorize">' . $PayPal->getDef('cfg_ec_transaction_method_authorize') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="transactionMethodSelection">{$input}</div>
EOHTML;
    }

  }
