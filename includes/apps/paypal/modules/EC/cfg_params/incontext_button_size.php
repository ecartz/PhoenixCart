<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_incontext_button_size {

    public $default = '2';
    public $title;
    public $description;
    public $sort_order = 220;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_incontext_button_size_title');
      $this->description = $PayPal->getDef('cfg_ec_incontext_button_size_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="incontextButtonSizeSmall" name="incontext_button_size" value="2"' . (PAYPAL_APP_EC_INCONTEXT_BUTTON_SIZE == '2' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="incontextButtonSizeSmall">' . $PayPal->getDef('cfg_ec_incontext_button_size_small') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="incontextButtonSizeTiny" name="incontext_button_size" value="1"' . (PAYPAL_APP_EC_INCONTEXT_BUTTON_SIZE == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="incontextButtonSizeTiny">' . $PayPal->getDef('cfg_ec_incontext_button_size_tiny') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="incontextButtonSizeMedium" name="incontext_button_size" value="3"' . (PAYPAL_APP_EC_INCONTEXT_BUTTON_SIZE == '3' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="incontextButtonSizeMedium">' . $PayPal->getDef('cfg_ec_incontext_button_size_medium') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="incontextButtonSizeSelection">{$input}</div>
EOHTML;
    }

  }
