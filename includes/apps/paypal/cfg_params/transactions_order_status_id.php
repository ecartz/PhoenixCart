<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_Cfg_transactions_order_status_id {

    public $default = '0';
    public $title;
    public $description;
    public $sort_order = 200;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_transactions_order_status_id_title');
      $this->description = $PayPal->getDef('cfg_transactions_order_status_id_desc');
    }

    public function getSetField() {
      $statuses_array = $GLOBALS['db']->fetch_all(sprintf(<<<'EOSQL'
SELECT orders_status_id AS id, orders_status_name AS `text`
 FROM orders_status
 WHERE language_id = %d AND public_flag = 0
 ORDER BY orders_status_name
EOSQL
        , (int)$_SESSION['languages_id']));

      $input = new Select('transactions_order_status_id', $statuses_array, ['id="inputTransactionsOrderStatusId"']);
      $input->set_selection((int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
