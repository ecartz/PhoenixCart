<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_EC_Cfg_page_style {

    public $default = '';
    public $title;
    public $description;
    public $sort_order = 600;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ec_page_style_title');
      $this->description = $PayPal->getDef('cfg_ec_page_style_desc');
    }

    public function getSetField() {
      $input = new Input('page_style', ['value' => PAYPAL_APP_EC_PAGE_STYLE, 'id' => 'inputEcPageStyle']);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
