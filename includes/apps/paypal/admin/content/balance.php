<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/
?>

<div class="card" id="ppAccountBalanceLive">
  <div class="card-header">
    <?= $PayPal->getDef('heading_live_account', ['account' => str_replace('_api1.', '@', $PayPal->getApiCredentials('live', 'username'))]) ?>
  </div>
  <div class="card-body">
    <div id="ppBalanceLiveInfo">
      <p><?= $PayPal->getDef('retrieving_balance_progress') ?></p>
    </div>
  </div>
</div>

<div class="card" id="ppAccountBalanceSandbox">
  <div class="card-header">
    <?= $PayPal->getDef('heading_sandbox_account', ['account' => str_replace('_api1.', '@', $PayPal->getApiCredentials('sandbox', 'username'))]) ?>
  </div>
  <div class="card-body">
    <div id="ppBalanceSandboxInfo">
      <p><?= $PayPal->getDef('retrieving_balance_progress') ?></p>
    </div>
  </div>
</div>

<div class="card" id="ppAccountBalanceNone" style="display: none;">
  <div class="card-body">
    <p><?= $PayPal->getDef('error_no_accounts_configured') ?></p>
  </div>
</div>

<script>
Phoenix.APP.PAYPAL.getBalance = function(type) {
  var def = {
    'error_balance_retrieval': '<?= addslashes($PayPal->getDef('error_balance_retrieval')) ?>'
  };

  var divId = 'ppBalance' + type.charAt(0).toUpperCase() + type.slice(1) + 'Info';

  $.get('<?= Guarantor::ensure_global('Admin')->link(
               'paypal.php', [
                 'action' => 'balance',
                 'subaction' => 'retrieve',
                 'type' => 'PPTYPE'])->set_separator_encoding(false)
         ?>'.replace('PPTYPE', type), function (data) {
    var balance = {};

    $('#' + divId).empty();

    try {
      data = $.parseJSON(data);
    } catch (ex) {
    }

    if ( (typeof data == 'object') && ('rpcStatus' in data) && (data['rpcStatus'] == 1) ) {
      if ( ('balance' in data) && (typeof data['balance'] == 'object') ) {
        balance = data['balance'];
      }
    } else if ( (typeof data == 'string') && (data.indexOf('rpcStatus') > -1) ) {
      var result = data.split("\n", 1);

      if ( result.length == 1 ) {
        var rpcStatus = result[0].split('=', 2);

        if ( rpcStatus[1] == 1 ) {
          var entries = data.split("\n");

          for ( var i = 0; i < entries.length; i++ ) {
            var entry = entries[i].split('=', 2);

            if ( (entry.length == 2) && (entry[0] != 'rpcStatus') ) {
              balance[entry[0]] = entry[1];
            }
          }
        }
      }
    }

    var pass = false;

    for ( var key in balance ) {
      pass = true;

      $('#' + divId).append('<p><strong>' + Phoenix.htmlSpecialChars(key) + ':</strong> ' + Phoenix.htmlSpecialChars(balance[key]) + '</p>');
    }

    if ( pass == false ) {
      $('#' + divId).append('<p>' + def['error_balance_retrieval'] + '</p>');
    }
  }).fail(function() {
    $('#' + divId).empty().append('<p>' + def['error_balance_retrieval'] + '</p>');
  });
};

$(function() {
  (function() {
    var pass = false;

    for ( var key in Phoenix.APP.PAYPAL.accountTypes ) {
      if ( Phoenix.APP.PAYPAL.accountTypes[key] == true ) {
        pass = true;

        Phoenix.APP.PAYPAL.getBalance(key);
      } else {
        $('#ppAccountBalance' + key.charAt(0).toUpperCase() + key.slice(1)).hide();
      }
    }

    if ( pass == false ) {
      $('#ppAccountBalanceNone').show();
    }
  })();
});
</script>
