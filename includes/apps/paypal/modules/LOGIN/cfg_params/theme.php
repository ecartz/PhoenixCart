<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class PayPal_LOGIN_Cfg_theme {

    public $default = 'Blue';
    public $title;
    public $description;
    public $sort_order = 600;

    public function __construct() {
      global $PayPal;

      $this->title = $PayPal->getDef('cfg_login_theme_title');
      $this->description = $PayPal->getDef('cfg_login_theme_desc');
    }

    public function getSetField() {
      global $PayPal;

      $input  = '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="themeSelectionBlue" name="theme" value="Blue"' . (PAYPAL_APP_LOGIN_THEME == 'Blue' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="themeSelectionBlue">' . $PayPal->getDef('cfg_login_theme_blue') . '</label>';
      $input .= '</div>';
      $input .= '<div class="custom-control custom-radio custom-control-inline">';
        $input .= '<input type="radio" class="custom-control-input" id="themeSelectionNeutral" name="theme" value="Neutral"' . (PAYPAL_APP_LOGIN_THEME == 'Neutral' ? ' checked="checked"' : '') . '>';
        $input .= '<label class="custom-control-label" for="themeSelectionNeutral">' . $PayPal->getDef('cfg_login_theme_neutral') . '</label>';
      $input .= '</div>';

      return <<<"EOHTML"
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3" id="themeSelection">{$input}</div>
EOHTML;

       $result;
    }

  }
