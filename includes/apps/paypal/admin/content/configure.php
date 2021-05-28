<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/
?>

<div class="row mb-1">
  <div class="col">
    <h5><?= $PayPal->getDef('paypal_installed') ?></h5>
    <div id="appPayPalToolbar">
      <?php
      foreach ( $PayPal->getModules() as $m ) {
        if ( $PayPal->isInstalled($m) ) {
          echo $PayPal->drawButton($PayPal->getModuleInfo($m, 'short_title'), Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'configure', 'module' => $m]), 'info', 'data-module="' . $m . '"') . "\n";
        }
      }
      echo $PayPal->drawButton($PayPal->getDef('section_general'), Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'configure', 'module' => 'G']), 'info', 'data-module="G"');
      ?>
    </div>
  </div>
  <div class="col">
    <h5><?= $PayPal->getDef('paypal_not_installed') ?></h5>
    <nav id="appPayPalToolbarMore" class="nav">
      <?php
      foreach ( $PayPal->getModules() as $m ) {
        if ( !$PayPal->isInstalled($m) ) {
          echo '<a class="nav-link btn btn-sm btn-secondary mr-1" title="' . $PayPal->getModuleInfo($m, 'short_title') . '" href="' . $GLOBALS['Admin']->link('paypal.php', ['action' => 'configure', 'module' => $m]) . '">' . $m . '</a>';
        }
      }
      ?>
    </nav>
  </div>
</div>

<?php
  if ( $PayPal->isInstalled($current_module) || ($current_module == 'G') ) {
    $current_module_title = ($current_module != 'G') ? $PayPal->getModuleInfo($current_module, 'title') : $PayPal->getDef('section_general');
    $req_notes = ($current_module != 'G') ? $PayPal->getModuleInfo($current_module, 'req_notes') : null;

    if ( is_array($req_notes) ) {
      foreach ( $req_notes as $rn ) {
        echo '<div class="alert alert-warning"><p>' . $rn . '</p></div>';
      }
    }
?>

<form name="paypalConfigure" action="<?= $GLOBALS['Admin']->link('paypal.php', ['action' => 'configure', 'subaction' => 'process', 'module' => $current_module]) ?>" method="post">

<h1 class="display-4"><?= $current_module_title ?></h1>

<div class="card">
  <div class="card-body">
    <?= implode('', $PayPal->getInputParameters($current_module)) ?>
  </div>
</div>

<div class="row">
  <div class="col">
    <p class="mt-2"><?= $PayPal->drawButton($PayPal->getDef('button_save'), null, 'success');?></p>
  </div>
  <?php
  if ( $current_module != 'G' ) {
    ?>
    <div class="col text-right">
      <button type="button" class="btn btn-danger mt-2" data-toggle="modal" data-target="#delModal">
        <?= $PayPal->getDef('dialog_uninstall_title') ?>
      </button>
    </div>
    <?php
  }
  ?>
  </div>
</div>

</form>

<?php
  } else {
?>

<h1 class="display-4"><?= $PayPal->getModuleInfo($current_module, 'title') ?></h1>

<div class="alert alert-info"><?= $PayPal->getModuleInfo($current_module, 'introduction') ?></div>

<p><?= $PayPal->drawButton($PayPal->getDef('button_install_title', ['title' => $PayPal->getModuleInfo($current_module, 'title')]), $GLOBALS['Admin']->link('paypal.php', ['action' => 'configure', 'subaction' => 'install', 'module' => $current_module]), 'success') ?></p>

<?php
  }
?>

<div class="modal fade" id="delModal" tabindex="-1" aria-labelledby="..." aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="..."><?= sprintf($PayPal->getDef('modal_uninstall_title'), $current_module) ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="<?= $PayPal->getDef('modal_uninstall_cancel') ?>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?= sprintf($PayPal->getDef('dialog_uninstall_body'), $PayPal->getModuleInfo($current_module, 'title')) ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $PayPal->getDef('modal_uninstall_cancel') ?></button>
        <a role="button" class="btn btn-danger" href="<?= $GLOBALS['Admin']->link('paypal.php', ['action' => 'configure', 'subaction' => 'uninstall', 'module' => $current_module]) ?>"><?= $PayPal->getDef('modal_uninstall_do_it') ?></a>
      </div>
    </div>
  </div>
</div>

<script>
$(function() {
  $('#appPayPalToolbar a[data-module="<?= $current_module ?>"]').addClass('active');
});
</script>
