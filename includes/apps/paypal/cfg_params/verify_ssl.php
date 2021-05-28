<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_Cfg_verify_ssl {

    public $default = '1';
    public $title;
    public $description;
    public $sort_order = 300;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_verify_ssl_title');
      $this->description = $PayPal->getDef('cfg_verify_ssl_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="verifySslSelectionTrue" name="verify_ssl" value="1"' . (PAYPAL_APP_VERIFY_SSL == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="verifySslSelectionTrue">' . $PayPal->getDef('cfg_verify_ssl_true') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="verifySslSelectionFalse" name="verify_ssl" value="0"' . (PAYPAL_APP_VERIFY_SSL == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="verifySslSelectionFalse">' . $PayPal->getDef('cfg_verify_ssl_false') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div id="verifySslSelection" class="mb-3">{$input}</div>
EOHTML;
    }

  }
