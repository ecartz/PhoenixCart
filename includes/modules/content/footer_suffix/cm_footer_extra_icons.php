<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  class cm_footer_extra_icons extends abstract_executable_module {

    const CONFIG_KEY_BASE = 'MODULE_CONTENT_FOOTER_EXTRA_ICONS_';

    public function __construct() {
      parent::__construct(__FILE__);
    }

    function execute() {
      if ( defined('MODULE_CONTENT_FOOTER_EXTRA_ICONS_TEXT') && !Text::is_empty(MODULE_CONTENT_FOOTER_EXTRA_ICONS_TEXT)) {
        $brand_icons = MODULE_CONTENT_FOOTER_EXTRA_ICONS_TEXT;
      } else {
        $brand_icons = explode(',', MODULE_CONTENT_FOOTER_EXTRA_ICONS_DISPLAY);
        if (empty($brand_icons)) {
          return;
        }
      }

      $tpl_data = [ 'group' => $this->group, 'file' => __FILE__ ];
      include 'includes/modules/content/cm_template.php';
    }

    protected function get_parameters() {
      return [
        'MODULE_CONTENT_FOOTER_EXTRA_ICONS_STATUS' => [
          'title' => 'Enable Payment Icons Footer Module',
          'value' => 'True',
          'desc' => 'Do you want to enable the Payment Icons content module?',
          'set_func' => "Config::select_one(['True', 'False'], ",
        ],
        'MODULE_CONTENT_FOOTER_EXTRA_ICONS_CONTENT_WIDTH' => [
          'title' => 'Content Width',
          'value' => '6',
          'desc' => 'What width container should the content be shown in? (12 = full width, 6 = half width).',
          'set_func' => "Config::select_one(['12', '11', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1'], ",
        ],
        'MODULE_CONTENT_FOOTER_EXTRA_ICONS_DISPLAY' => [
          'title' => 'Icons',
          'value' => 'apple-pay,bitcoin,cc-paypal',
          'desc' => 'Icons to display.',
        ],
        'MODULE_CONTENT_FOOTER_EXTRA_ICONS_SORT_ORDER' => [
          'title' => 'Sort Order',
          'value' => '20',
          'desc' => 'Sort order of display. Lowest is displayed first.',
        ],
      ];
    }
  }
