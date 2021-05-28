<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_instant_update {

    public $default = '1';
    public $title;
    public $description;
    public $sort_order = 400;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_instant_update_title');
      $this->description = $PayPal->getDef('cfg_ec_instant_update_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="instantUpdateSelectionEnabled" name="instant_update" value="1"' . (PAYPAL_APP_EC_INSTANT_UPDATE == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="instantUpdateSelectionEnabled">' . $PayPal->getDef('cfg_ec_instant_update_enabled') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="instantUpdateSelectionDisabled" name="instant_update" value="0"' . (PAYPAL_APP_EC_INSTANT_UPDATE == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="instantUpdateSelectionDisabled">' . $PayPal->getDef('cfg_ec_instant_update_disabled') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="instantUpdateSelection">{$input}</div>
EOHTML;
    }

  }
