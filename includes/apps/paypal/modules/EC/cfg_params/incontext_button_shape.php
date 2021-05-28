<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_incontext_button_shape {

    public $default = '1';
    public $title;
    public $description;
    public $sort_order = 230;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_incontext_button_shape_title');
      $this->description = $PayPal->getDef('cfg_ec_incontext_button_shape_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="incontextButtonShapePill" name="incontext_button_shape" value="1"' . (PAYPAL_APP_EC_INCONTEXT_BUTTON_SHAPE == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="incontextButtonShapePill">' . $PayPal->getDef('cfg_ec_incontext_button_shape_pill') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="incontextButtonShapeRect" name="incontext_button_shape" value="2"' . (PAYPAL_APP_EC_INCONTEXT_BUTTON_SHAPE == '2' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="incontextButtonShapeRect">' . $PayPal->getDef('cfg_ec_incontext_button_shape_rect') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="incontextButtonShapeSelection">{$input}</div>
EOHTML;
    }

  }
