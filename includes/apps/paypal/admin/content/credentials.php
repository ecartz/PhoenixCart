<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/
?>

<div id="appPayPalToolbar" style="padding-bottom: 15px;">
  <?= $PayPal->drawButton($PayPal->getDef('section_paypal'), Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'credentials', 'module' => 'PP']), 'info', 'data-module="PP"') ?>
  <?= $PayPal->drawButton($PayPal->getDef('section_payflow'), $GLOBALS['Admin']->link('paypal.php', ['action' => 'credentials', 'module' => 'PF']), 'info', 'data-module="PF"') ?>

<?php
  if ($current_module == 'PP') {
?>

  <span style="float: right;">
    <?= $PayPal->drawButton($PayPal->getDef('button_retrieve_live_credentials'), $GLOBALS['Admin']->link('paypal.php', ['action' => 'start', 'subaction' => 'process', 'type' => 'live']), 'warning') ?>
    <?= $PayPal->drawButton($PayPal->getDef('button_retrieve_sandbox_credentials'), $GLOBALS['Admin']->link('paypal.php', ['action' => 'start', 'subaction' => 'process', 'type' => 'sandbox']), 'warning') ?>
  </span>

<?php
  }
?>

</div>

<form name="paypalCredentials" action="<?= $GLOBALS['Admin']->link('paypal.php', ['action' => 'credentials', 'subaction' => 'process', 'module' => $current_module]) ?>" method="post">

<?php
  if ( $current_module == 'PP' ) {
  ?>

  <div class="row row-cols-1 row-cols-md-2">
    <div class="col mb-4">
      <div class="card">
        <div class="card-header">
          <?= $PayPal->getDef('paypal_live_title') ?>
        </div>
        <div class="card-body">
          <div class="form-group row">
            <label for="live_username" class="col-form-label col-12"><?= $PayPal->getDef('paypal_live_api_username') ?></label>
            <div class="col-12">
              <?= new Input('live_username', ['value' => PAYPAL_APP_LIVE_API_USERNAME]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="live_password" class="col-form-label col-12"><?= $PayPal->getDef('paypal_live_api_password') ?></label>
            <div class="col-12">
              <?= new Input('live_password', ['value' => PAYPAL_APP_LIVE_API_PASSWORD]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="live_signature" class="col-form-label col-12"><?= $PayPal->getDef('paypal_live_api_signature') ?></label>
            <div class="col-12">
              <?= new Input('live_signature', ['value' => PAYPAL_APP_LIVE_API_SIGNATURE]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="live_merchant_id" class="col-form-label col-12"><?= $PayPal->getDef('paypal_live_merchant_id') ?></label>
            <div class="col-12">
              <?= new Input('live_merchant_id', ['value' => PAYPAL_APP_LIVE_MERCHANT_ID]) ?>
              <small class="form-text text-muted">
                <?= $PayPal->getDef('paypal_live_merchant_id_desc') ?>
              </small>
            </div>
          </div>
          <div class="form-group row">
            <label for="live_email" class="col-form-label col-12"><?= $PayPal->getDef('paypal_live_email_address') ?></label>
            <div class="col-12">
              <?= new Input('live_email', ['value' => PAYPAL_APP_LIVE_SELLER_EMAIL]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="live_email_primary" class="col-form-label col-12"><?= $PayPal->getDef('paypal_live_primary_email_address') ?></label>
            <div class="col-12">
              <?= new Input('live_email_primary', ['value' => PAYPAL_APP_LIVE_SELLER_EMAIL_PRIMARY]) ?>
              <small class="form-text text-muted">
                <?= $PayPal->getDef('paypal_live_primary_email_address_desc') ?>
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col mb-4">
      <div class="card">
        <div class="card-header">
          <?= $PayPal->getDef('paypal_sandbox_title') ?>
        </div>
        <div class="card-body">
          <div class="form-group row">
            <label for="sandbox_username" class="col-form-label col-12"><?= $PayPal->getDef('paypal_sandbox_api_username') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_username', ['value' => PAYPAL_APP_SANDBOX_API_USERNAME]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="sandbox_password" class="col-form-label col-12"><?= $PayPal->getDef('paypal_sandbox_api_password') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_password', ['value' => PAYPAL_APP_SANDBOX_API_PASSWORD]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="sandbox_signature" class="col-form-label col-12"><?= $PayPal->getDef('paypal_sandbox_api_signature') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_signature', ['value' => PAYPAL_APP_SANDBOX_API_SIGNATURE]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="sandbox_merchant_id" class="col-form-label col-12"><?= $PayPal->getDef('paypal_sandbox_merchant_id') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_merchant_id', ['value' => PAYPAL_APP_SANDBOX_MERCHANT_ID]) ?>
              <small class="form-text text-muted">
                <?= $PayPal->getDef('paypal_sandbox_merchant_id_desc') ?>
              </small>
            </div>
          </div>
          <div class="form-group row">
            <label for="sandbox_email" class="col-form-label col-12"><?= $PayPal->getDef('paypal_sandbox_email_address') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_email', ['value' => PAYPAL_APP_SANDBOX_SELLER_EMAIL]) ?>
            </div>
          </div>

          <div class="form-group row">
            <label for="sandbox_email_primary" class="col-form-label col-12"><?= $PayPal->getDef('paypal_sandbox_primary_email_address') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_email_primary', ['value' => PAYPAL_APP_SANDBOX_SELLER_EMAIL_PRIMARY]) ?>
              <small class="form-text text-muted">
                <?= $PayPal->getDef('paypal_sandbox_primary_email_address_desc') ?>
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php
  } elseif ( $current_module == 'PF' ) {
?>

  <div class="row row-cols-1 row-cols-md-2">
    <div class="col mb-4">
      <div class="card">
        <div class="card-header">
          <?= $PayPal->getDef('payflow_live_title') ?>
        </div>
        <div class="card-body">
          <div class="form-group row">
            <label for="live_partner" class="col-form-label col-12"><?= $PayPal->getDef('payflow_live_partner') ?></label>
            <div class="col-12">
              <?= new Input('live_partner', ['value' => PAYPAL_APP_LIVE_PF_PARTNER]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="live_vendor" class="col-form-label col-12"><?= $PayPal->getDef('payflow_live_merchant_login') ?></label>
            <div class="col-12">
              <?= new Input('live_vendor', ['value' => PAYPAL_APP_LIVE_PF_VENDOR]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="live_user" class="col-form-label col-12"><?= $PayPal->getDef('payflow_live_user') ?></label>
            <div class="col-12">
              <?= new Input('live_user', ['value' => PAYPAL_APP_LIVE_PF_USER]) ?>
            </div>
          </div>
          <div class="form-group row">
            <label for="live_password" class="col-form-label col-12"><?= $PayPal->getDef('payflow_live_password') ?></label>
            <div class="col-12">
              <?= new Input('live_password', ['value' => PAYPAL_APP_LIVE_PF_PASSWORD]) ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col mb-4">
      <div class="card">
        <div class="card-header">
          <?= $PayPal->getDef('payflow_sandbox_title') ?>
        </div>
        <div class="card-body">
          <div class="form-group row">
            <label for="sandbox_partner" class="col-form-label col-12"><?= $PayPal->getDef('payflow_sandbox_partner') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_partner', ['value' => PAYPAL_APP_SANDBOX_PF_PARTNER]) ?>
            </div>
          </div>

          <div class="form-group row">
            <label for="sandbox_vendor" class="col-form-label col-12"><?= $PayPal->getDef('payflow_sandbox_merchant_login') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_vendor', ['value' => PAYPAL_APP_SANDBOX_PF_VENDOR]) ?>
            </div>
          </div>

          <div class="form-group row">
            <label for="sandbox_user" class="col-form-label col-12"><?= $PayPal->getDef('payflow_sandbox_user') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_user', ['value' => PAYPAL_APP_SANDBOX_PF_USER]) ?>
            </div>
          </div>

          <div class="form-group row">
            <label for="sandbox_password" class="col-form-label col-12"><?= $PayPal->getDef('payflow_sandbox_password') ?></label>
            <div class="col-12">
              <?= new Input('sandbox_password', ['value' => PAYPAL_APP_SANDBOX_PF_PASSWORD]) ?>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

<?php
  }
?>

<p><?= $PayPal->drawButton($PayPal->getDef('button_save'), null, 'success') ?></p>

</form>

<script>
$(function() {
  $('#appPayPalToolbar a[data-module="<?= $current_module ?>"]').addClass('active');
});
</script>
