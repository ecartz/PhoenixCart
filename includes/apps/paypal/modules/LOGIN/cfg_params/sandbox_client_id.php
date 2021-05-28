<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_LOGIN_Cfg_sandbox_client_id {

    public $default = '';
    public $title;
    public $description;
    public $sort_order = 400;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_login_sandbox_client_id_title');
      $this->description = $PayPal->getDef('cfg_login_sandbox_client_id_desc');
    }

    public function getSetField() {
      $input = new Input('sandbox_client_id', ['value' => PAYPAL_APP_LOGIN_SANDBOX_CLIENT_ID, 'id' => 'inputLogInSandboxClientId']);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
