<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_HS_Cfg_zone {

    public $default = '0';
    public $title;
    public $description;
    public $sort_order = 500;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_hs_zone_title');
      $this->description = $PayPal->getDef('cfg_hs_zone_desc');
    }

    public function getSetField() {
      global $PayPal;

      $geo_zones = array_merge([['id' => '0', 'text' => $PayPal->getDef('cfg_hs_zone_global')]],
        $GLOBALS['db']->fetch_all("SELECT geo_zone_id AS id, geo_zone_name AS `text` FROM geo_zones ORDER BY geo_zone_name"));

      $input = new Select('zone', $geo_zones, ['id' => 'inputHsZone']);
      $input->set_selection(PAYPAL_APP_HS_ZONE);

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOHTML;
    }

  }
