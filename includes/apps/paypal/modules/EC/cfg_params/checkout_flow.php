<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_checkout_flow {

    public $default = '1';
    public $title;
    public $description;
    public $sort_order = 200;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_checkout_flow_title');
      $this->description = $PayPal->getDef('cfg_ec_checkout_flow_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="checkoutFlowSelectionInContext" name="checkout_flow" value="1"' . (PAYPAL_APP_EC_CHECKOUT_FLOW == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="checkoutFlowSelectionInContext">' . $PayPal->getDef('cfg_ec_checkout_flow_in_context') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="checkoutFlowSelectionDefault" name="checkout_flow" value="0"' . (PAYPAL_APP_EC_CHECKOUT_FLOW == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="checkoutFlowSelectionDefault">' . $PayPal->getDef('cfg_ec_checkout_flow_default') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"

<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="checkoutFlowSelection">{$input}</div>
EOHTML;
    }

  }
