<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_account_optional {

    public $default = '0';
    public $title;
    public $description;
    public $sort_order = 300;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_account_optional_title');
      $this->description = $PayPal->getDef('cfg_ec_account_optional_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="accountOptionalSelectionTrue" name="account_optional" value="1"' . (PAYPAL_APP_EC_ACCOUNT_OPTIONAL == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="accountOptionalSelectionTrue">' . $PayPal->getDef('cfg_ec_account_optional_true') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="accountOptionalSelectionFalse" name="account_optional" value="0"' . (PAYPAL_APP_EC_ACCOUNT_OPTIONAL == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="accountOptionalSelectionFalse">' . $PayPal->getDef('cfg_ec_account_optional_false') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="accountOptionalSelection">{$input}</div>
EOHTML;
    }

  }
