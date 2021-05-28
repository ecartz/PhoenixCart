<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_LOGIN_Cfg_sandbox_secret {

    public $default = '';
    public $sort_order = 500;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_login_sandbox_secret_title');
      $this->description = $PayPal->getDef('cfg_login_sandbox_secret_desc');
    }

    public function getSetField() {
      $input = new Input('sandbox_secret', ['value' => PAYPAL_APP_LOGIN_SANDBOX_SECRET, 'id' => 'inputLogInSandboxSecret']);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
