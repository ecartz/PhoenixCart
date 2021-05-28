<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_Cfg_proxy {

    public $default = '';
    public $title;
    public $description;
    public $sort_order = 400;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_proxy_title');
      $this->description = $PayPal->getDef('cfg_proxy_desc');
    }

    public function getSetField() {
      $input = new Input('proxy', ['value' => PAYPAL_APP_PROXY, 'id' => 'inputProxy']);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}  </p>

<div>{$input}</div>
EOHTML;
    }

  }
