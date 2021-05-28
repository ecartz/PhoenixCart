<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_PS_Cfg_ewp_paypal_cert {

    public $default = '';
    public $title;
    public $description;
    public $sort_order = 1100;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ps_ewp_paypal_cert_title');
      $this->description = $PayPal->getDef('cfg_ps_ewp_paypal_cert_desc');
    }

    public function getSetField() {
      $input = new Input('ewp_paypal_cert', ['value' => PAYPAL_APP_PS_EWP_PAYPAL_CERT, 'id' => 'inputPsEwpPayPalCert']);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
