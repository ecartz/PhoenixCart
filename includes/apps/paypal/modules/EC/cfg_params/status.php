<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_status {

    public $default = '1';
    public $title;
    public $description;
    public $sort_order = 100;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_status_title');
      $this->description = $PayPal->getDef('cfg_ec_status_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="statusSelectionLive" name="status" value="1"' . (PAYPAL_APP_EC_STATUS == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="statusSelectionLive">' . $PayPal->getDef('cfg_ec_status_live') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="statusSelectionSandbox" name="status" value="0"' . (PAYPAL_APP_EC_STATUS == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="statusSelectionSandbox">' . $PayPal->getDef('cfg_ec_status_sandbox') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="statusSelectionDisabled" name="status" value="-1"' . (PAYPAL_APP_EC_STATUS == '-1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="statusSelectionDisabled">' . $PayPal->getDef('cfg_ec_status_disabled') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="statusSelection">{$input}</div>
EOHTML;
    }

  }
