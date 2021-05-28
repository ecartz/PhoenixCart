<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_HS_Cfg_order_status_id {

    public $default = '0';
    public $title;
    public $description;
    public $sort_order = 400;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_hs_order_status_id_title');
      $this->description = $PayPal->getDef('cfg_hs_order_status_id_desc');
    }

    public function getSetField() {
      global $PayPal;

      $statuses = array_merge([['id' => '0', 'text' => $PayPal->getDef('cfg_hs_order_status_id_default')]],
        $GLOBALS['db']->fetch_all(sprintf(<<<'EOSQL'
SELECT orders_status_id AS id, orders_status_name AS `text` FROM orders_status WHERE language_id = %d ORDER BY orders_status_name
EOSQL
          , (int)$_SESSION['languages_id'])));

      $input = new Select('order_status_id', $statuses, ['value' => PAYPAL_APP_HS_ORDER_STATUS_ID, 'id' => 'inputHsOrderStatusId']);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
