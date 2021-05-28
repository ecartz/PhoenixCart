<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_LOGIN_Cfg_content_width {

    public $default = 'Full';
    public $title;
    public $description;
    public $app_configured = false;
    public $set_func = 'Config::select_one([\'Full\', \'Half\'], ';

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_login_content_width_title');
      $this->description = $PayPal->getDef('cfg_login_content_width_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input = '<input type="radio" id="contentWidthSelectionHalf" name="content_width" value="Half"' . (PAYPAL_APP_LOGIN_CONTENT_WIDTH == 'Half' ? ' checked="checked"' : '') . '><label for="contentWidthSelectionHalf">' . $PayPal->getDef('cfg_login_content_width_half') . '</label>' .
               '<input type="radio" id="contentWidthSelectionFull" name="content_width" value="Full"' . (PAYPAL_APP_LOGIN_CONTENT_WIDTH == 'Full' ? ' checked="checked"' : '') . '><label for="contentWidthSelectionFull">' . $PayPal->getDef('cfg_login_content_width_full') . '</label>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="contentWidthSelection">{$input}</div>
EOHTML;
    }

  }
