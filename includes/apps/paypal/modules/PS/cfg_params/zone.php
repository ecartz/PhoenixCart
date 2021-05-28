<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_PS_Cfg_zone {

    public $default = '0';
    public $title;
    public $description;
    public $sort_order = 600;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_ps_zone_title');
      $this->description = $PayPal->getDef('cfg_ps_zone_desc');
    }

    public function getSetField() {
      global $PayPal;

      $zone_classes = array_merge(
        [['id' => '0', 'text' => $PayPal->getDef('cfg_ps_zone_global')]],
        $GLOBALS['db']->fetch_all("SELECT geo_zone_id AS id, geo_zone_name AS `text` FROM geo_zones ORDER BY geo_zone_name"));

      $input = new Select('zone', $zone_classes, ['id' => 'inputPsZone']);
      $input->set_selection(PAYPAL_APP_PS_ZONE);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
