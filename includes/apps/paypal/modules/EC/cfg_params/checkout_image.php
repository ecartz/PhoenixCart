<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_checkout_image {

    public $default = '0';
    public $title;
    public $description;
    public $sort_order = 500;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_checkout_image_title');
      $this->description = $PayPal->getDef('cfg_ec_checkout_image_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="checkoutImageSelectionStatic" name="checkout_image" value="0"' . (PAYPAL_APP_EC_CHECKOUT_FLOW == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="checkoutImageSelectionStatic">' . $PayPal->getDef('cfg_ec_checkout_image_static') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="checkoutImageSelectionDynamic" name="checkout_image" value="1"' . (PAYPAL_APP_EC_CHECKOUT_FLOW == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="checkoutImageSelectionDynamic">' . $PayPal->getDef('cfg_ec_checkout_image_dynamic') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="checkoutImageSelection">{$input}</div>
EOHTML;
    }

  }
