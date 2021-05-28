<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_PS_Cfg_ewp_private_key {

    public $default = '';
    public $title;
    public $description;
    public $sort_order = 800;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ps_ewp_private_key_title');
      $this->description = $PayPal->getDef('cfg_ps_ewp_private_key_desc');
    }

    public function getSetField() {
      $input = new Input('ewp_private_key', ['value' => PAYPAL_APP_PS_EWP_PRIVATE_KEY, 'id' => 'inputPsEwpPrivateKey']);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
