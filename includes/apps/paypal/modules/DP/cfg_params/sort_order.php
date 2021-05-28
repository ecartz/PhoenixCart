<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_DP_Cfg_sort_order {

    public $default = '0';
    public $title;
    public $description;
    public $app_configured = false;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_dp_sort_order_title');
      $this->description = $PayPal->getDef('cfg_dp_sort_order_desc');
    }

    public function getSetField() {
      $input = new Input('sort_order', ['value' => PAYPAL_APP_DP_SORT_ORDER, 'id' => 'inputDpSortOrder']);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
