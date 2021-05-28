<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_Cfg_gateway {

    public $default = '1';
    public $title;
    public $description;
    public $sort_order = 100;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_gateway_title');
      $this->description = $PayPal->getDef('cfg_gateway_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="gatewaySelectionPayPal" name="gateway" value="1"' . (PAYPAL_APP_GATEWAY == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="gatewaySelectionPayPal">' . $PayPal->getDef('cfg_gateway_paypal') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="gatewaySelectionPayflow" name="gateway" value="0"' . (PAYPAL_APP_GATEWAY == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="gatewaySelectionPayflow">' . $PayPal->getDef('cfg_gateway_payflow') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div id="gatewaySelection" class="mb-3">{$input}</div>
EOHTML;
    }

  }
