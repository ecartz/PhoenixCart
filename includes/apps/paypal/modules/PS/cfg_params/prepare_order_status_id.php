<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_PS_Cfg_prepare_order_status_id {

    public $default = '0';
    public $title;
    public $description;
    public $sort_order = 400;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ps_prepare_order_status_id_title');
      $this->description = $PayPal->getDef('cfg_ps_prepare_order_status_id_desc');
      $this->default = $this->determine_status_id();
    }

    public function determine_status_id() {
      if ( defined('PAYPAL_APP_PS_PREPARE_ORDER_STATUS_ID') ) {
        return PAYPAL_APP_PS_PREPARE_ORDER_STATUS_ID;
      }

      $check_query = $GLOBALS['db']->query("SELECT orders_status_id FROM orders_status WHERE orders_status_name = 'Preparing [PayPal Standard]' LIMIT 1");
      if ($check = $check_query->fetch_assoc()) {
        return $check['orders_status_id'];
      }

      $status = $GLOBALS['db']->query("SELECT MAX(orders_status_id) + 1 AS id FROM orders_status")->fetch_assoc();

      $sql_data = [
        'orders_status_id' => (int)$status['id'],
        'orders_status_name' => 'Preparing [PayPal Standard]',
      ];

      $flags_query = $GLOBALS['db']->query("DESCRIBE orders_status public_flag");
      if (mysqli_num_rows($flags_query) == 1) {
        $sql_data['public_flag'] = 0;
        $sql_data['downloads_flag'] = 0;
      }

      foreach (language::load_all() as $language) {
        $sql_data['language_id'] = (int)$language['id'];
        $GLOBALS['db']->perform('orders_status', $sql_data);
      }

      return $status['id'];
    }

    public function getSetField() {
      global $PayPal;

      $statuses = array_merge([['id' => '0', 'text' => $PayPal->getDef('cfg_ps_prepare_order_status_id_default')]],
        $GLOBALS['db']->fetch_all(sprintf(<<<'EOSQL'
SELECT orders_status_id AS id, orders_status_name AS `text` FROM orders_status WHERE language_id = %d ORDER BY orders_status_name
EOSQL
        , (int)$_SESSION['languages_id'])));

      $input = new Select('prepare_order_status_id', $statuses, ['id' => 'inputPsPrepareOrderStatusId']);
      $input->set_selection(PAYPAL_APP_PS_PREPARE_ORDER_STATUS_ID);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }
  }
