<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/
?>

<div class="row row-cols-1 row-cols-md-2">
  <div class="col mb-4">
    <div class="card">
      <div class="card-header">
        <?= $PayPal->getDef('online_documentation_title') ?>
      </div>
      <div class="card-body">
        <div class="pp-panel pp-panel-info">
          <?= $PayPal->getDef('online_documentation_body', ['button_online_documentation' => $PayPal->drawButton($PayPal->getDef('button_online_documentation'), 'https://phoenixcart.org/phoenixcartwiki/index.php?title=Start', 'info', 'target="_blank" rel="noreferrer"')]) ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col mb-4">
    <div class="card">
      <div class="card-header">
        <?= $PayPal->getDef('online_forum_title') ?>
      </div>
      <div class="card-body">
        <div class="pp-panel pp-panel-warning">
          <?= $PayPal->getDef('online_forum_body', ['button_online_forum' => $PayPal->drawButton($PayPal->getDef('button_online_forum'), 'https://phoenixcart.org/forum/', 'warning', 'target="_blank" rel="noreferrer"')]) ?>
        </div>
      </div>
    </div>
  </div>
</div>
