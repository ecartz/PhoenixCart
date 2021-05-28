<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_incontext_button_color {

    public $default = '1';
    public $title;
    public $description;
    public $sort_order = 210;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_incontext_button_color_title');
      $this->description = $PayPal->getDef('cfg_ec_incontext_button_color_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="incontextButtonColorSelectionGold" name="incontext_button_color" value="1"' . (PAYPAL_APP_EC_INCONTEXT_BUTTON_COLOR == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="incontextButtonColorSelectionGold">' . $PayPal->getDef('cfg_ec_incontext_button_color_gold') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="incontextButtonColorSelectionBlue" name="incontext_button_color" value="2"' . (PAYPAL_APP_EC_INCONTEXT_BUTTON_COLOR == '2' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="incontextButtonColorSelectionBlue">' . $PayPal->getDef('cfg_ec_incontext_button_color_blue') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="incontextButtonColorSelectionSilver" name="incontext_button_color" value="3"' . (PAYPAL_APP_EC_INCONTEXT_BUTTON_COLOR == '3' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="incontextButtonColorSelectionSilver">' . $PayPal->getDef('cfg_ec_incontext_button_color_silver') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="incontextButtonColorSelection">{$input}</div>
EOHTML;
    }

  }
