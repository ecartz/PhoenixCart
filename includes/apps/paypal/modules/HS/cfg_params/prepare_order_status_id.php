<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  class PayPal_HS_Cfg_prepare_order_status_id {

    public $default = '0';
    public $title;
    public $description;
    public $sort_order = 300;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_hs_prepare_order_status_id_title');
      $this->description = $PayPal->getDef('cfg_hs_prepare_order_status_id_desc');
      $this->default = $this->determine_status_id();
    }

    public function determine_status_id() {
      if ( defined('PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID') ) {
        return PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID;
      }

      $check_query = $GLOBALS['db']->query("SELECT orders_status_id FROM orders_status WHERE orders_status_name = 'Preparing [PayPal Pro HS]' LIMIT 1");
      if ($check = $check_query->fetch_assoc()) {
        return $check['orders_status_id'];
      }

      $status = $GLOBALS['db']->query("SELECT MAX(orders_status_id) + 1 AS id FROM orders_status")->fetch_assoc();

      $sql_data = [
        'orders_status_id' => (int)$status['id'],
        'orders_status_name' => 'Preparing [PayPal Pro HS]',
        'public_flag' => 0,
        'downloads_flag' => 0,
      ];

      foreach (language::load_all() as $language) {
        $sql_data['language_id'] = (int)$language['id'];
        $GLOBALS['db']->perform('orders_status', $sql_data);
      }

      return $status['id'];
    }

    public function getSetField() {
      global $PayPal;

      $statuses = array_merge([['id' => '0', 'text' => $PayPal->getDef('cfg_hs_prepare_order_status_id_default')]],
        $GLOBALS['db']->fetch_all(sprintf(<<<'EOSQL'
SELECT orders_status_id AS id, orders_status_name AS `text` FROM orders_status WHERE language_id = %d ORDER BY orders_status_name
EOSQL
        , (int)$_SESSION['languages_id'])));

      $input = new Select('prepare_order_status_id', $statuses, ['id' => 'inputHsPrepareOrderStatusId']);
      $input->set_selection(PAYPAL_APP_HS_PREPARE_ORDER_STATUS_ID);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
