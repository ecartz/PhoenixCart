<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_PS_Cfg_ewp_status {

    public $default = '-1';
    public $title;
    public $description;
    public $sort_order = 700;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ps_ewp_status_title');
      $this->description = $PayPal->getDef('cfg_ps_ewp_status_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="ewpStatusSelectionTrue" name="ewp_status" value="1"' . (PAYPAL_APP_PS_EWP_STATUS == '1' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="ewpStatusSelectionTrue">' . $PayPal->getDef('cfg_ps_ewp_status_true') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="ewpStatusSelectionFalse" name="ewp_status" value="0"' . (PAYPAL_APP_PS_EWP_STATUS == '0' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="ewpStatusSelectionFalse">' . $PayPal->getDef('cfg_ps_ewp_status_false') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }
  }
