<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  if ( !class_exists('PayPal') ) {
    include(DIR_FS_CATALOG . 'includes/apps/paypal/PayPal.php');
  }

  class paypal_hook_admin_orders_tab {

    function __construct() {
      $this->_app = Guarantor::ensure_global('PayPal');

      $this->_app->loadLanguageFile('hooks/admin/orders/tab.php');
    }

    function execute() {
      if (!defined('PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID')) {
        return;
      }

      global $oID, $base_url;

      $output = '';
      $status = [];

      $ppstatus_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments
 FROM orders_status_history
 WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE 'Transaction ID:%%'
 ORDER BY date_added DESC
 LIMIT 1
EOSQL
        , (int)$oID, (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));
      if ( mysqli_num_rows($ppstatus_query) ) {
        $ppstatus = $ppstatus_query->fetch_assoc();

        foreach ( array_filter(explode("\n", $ppstatus['comments'])) as $s ) {
          if (strpos($s, ':') !== false) {
            $entry = explode(':', $s, 2);

            $status[trim($entry[0])] = trim($entry[1]);
          }
        }

        if ( isset($status['Transaction ID']) ) {
          $order = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT o.orders_id, o.payment_method, o.currency, o.currency_value, ot.value AS total
 FROM orders o INNER JOIN orders_total ot ON o.orders_id = ot.orders_id
 WHERE o.orders_id = %d AND ot.class = 'ot_total'
EOSQL
            , (int)$oID))->fetch_assoc();

          $pp_server = (stripos($order['payment_method'], 'sandbox') === false) ? 'live' : 'sandbox';

          $info_button = $this->_app->drawButton($this->_app->getDef('button_details'), Guarantor::ensure_global('Admin')->link('orders.php', [
            'page' => (int)($_GET['page'] ?? 1),
            'oID' => $oID,
            'action' => 'edit',
            'tabaction' => 'getTransactionDetails',
          ]), 'primary', null, true);
          $capture_button = $this->getCaptureButton($status, $order);
          $void_button = $this->getVoidButton($status, $order);
          $refund_button = $this->getRefundButton($status, $order);
          $paypal_button = $this->_app->drawButton(
            $this->_app->getDef('button_view_at_paypal'),
            'https://www.' . ($pp_server == 'sandbox' ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=' . $status['Transaction ID'],
            'info', 'target="_blank" rel="noreferrer"', true);

          $tab_title = addslashes($this->_app->getDef('tab_title'));
          $tab_link = '#section_paypal_content';

          $output = <<<"EOD"
<script>
$(function() {
  $('#orderTabs ul').append('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="{$tab_link}" role="tab">{$tab_title}</a></li>');
});
</script>

<div class="tab-pane fade" id="section_paypal_content" role="tabpanel">
  {$info_button} {$capture_button} {$void_button} {$refund_button} {$paypal_button}
</div>
EOD;

        }
      }

      return $output;
    }

    function getCaptureButton($status, $order) {
      $output = '';

      if ( ($status['Pending Reason'] == 'authorization') || ($status['Payment Status'] == 'In-Progress') ) {
        $v_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments
 FROM orders_status_history
 WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE '%%PayPal App: Void (%%'
 LIMIT 1
EOSQL
          , (int)$order['orders_id'], (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));

        if ( !mysqli_num_rows($v_query) ) {
          $capture_total = $GLOBALS['currencies']->format_raw($order['total'], true, $order['currency'], $order['currency_value']);

          $c_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments
 FROM orders_status_history
 WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE 'PayPal App: Capture (%%'
EOSQL
            , (int)$order['orders_id'], (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));
          while ( $c = $c_query->fetch_assoc() ) {
            if ( preg_match('/^PayPal App\: Capture \(([0-9\.]+)\)\n/', $c['comments'], $c_matches) ) {
              $capture_total -= $GLOBALS['currencies']->format_raw($c_matches[1], true, $order['currency'], 1);
            }
          }

          if ( $capture_total > 0 ) {
            $output .= $this->_app->drawButton($this->_app->getDef('button_dialog_capture'), '#', 'success', 'data-button="paypalButtonDoCapture"', true);

            $dialog_title = htmlspecialchars($this->_app->getDef('dialog_capture_title'));
            $dialog_body = $this->_app->getDef('dialog_capture_body');
            $field_amount_title = $this->_app->getDef('dialog_capture_amount_field_title');
            $field_last_capture_title = $this->_app->getDef('dialog_capture_last_capture_field_title', ['currency' => $order['currency']]);
            $capture_link = Guarantor::ensure_global('Admin')->link('orders.php', [
              'page' => (int)($_GET['page'] ?? 1),
              'oID' => $order['orders_id'],
              'action' => 'edit',
              'tabaction' => 'doCapture',
            ]);
            $capture_currency = $order['currency'];
            $dialog_button_capture = addslashes($this->_app->getDef('dialog_capture_button_capture'));
            $dialog_button_cancel = addslashes($this->_app->getDef('dialog_capture_button_cancel'));

            $output .= <<<EOD
<div id="paypal-dialog-capture" title="{$dialog_title}">
  <form id="ppCaptureForm" action="{$capture_link}" method="post">
    <p>{$dialog_body}</p>

    <p>
      <label for="ppCaptureAmount"><strong>{$field_amount_title}</strong></label>
      <input type="text" name="ppCaptureAmount" value="{$capture_total}" id="ppCaptureAmount" style="text-align: right;" />
      {$capture_currency}
    </p>

    <p id="ppPartialCaptureInfo" style="display: none;">
      <input type="checkbox" name="ppCaptureComplete" value="true" id="ppCaptureComplete" />
      <label for="ppCaptureComplete">{$field_last_capture_title}</label>
    </p>
  </form>
</div>

<script>
$(function() {
  $('#paypal-dialog-capture').dialog({
    autoOpen: false,
    resizable: false,
    modal: true,
    buttons: {
      "{$dialog_button_capture}": function() {
        $('#ppCaptureForm').submit();
      },
      "{$dialog_button_cancel}": function() {
        $(this).dialog('close');
      }
    }
  });

  $('a[data-button="paypalButtonDoCapture"]').click(function(e) {
    e.preventDefault();

    $('#paypal-dialog-capture').dialog('open');
  });

  (function() {
    var ppCaptureTotal = {$capture_total};

    $('#ppCaptureAmount').keyup(function() {
      if (this.value != this.value.replace(/[^0-9\.]/g, '')) {
        this.value = this.value.replace(/[^0-9\.]/g, '');
      }

      if ( this.value < ppCaptureTotal ) {
        $('#ppCaptureVoidedValue').text((ppCaptureTotal - this.value).toFixed(2));
        $('#ppPartialCaptureInfo').show();
      } else {
        $('#ppPartialCaptureInfo').hide();
      }
    });
  })();
});
</script>
EOD;
          }
        }
      }

      return $output;
    }

    function getVoidButton($status, $order) {
      $output = '';

      if ( $status['Pending Reason'] == 'authorization' ) {
        $v_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments
 FROM orders_status_history
 WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE '%%PayPal App: Void (%%'
 LIMIT 1
EOSQL
          , (int)$order['orders_id'], (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));

        if ( !mysqli_num_rows($v_query) ) {
          $capture_total = $GLOBALS['currencies']->format_raw($order['total'], true, $order['currency'], $order['currency_value']);

          $c_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments
 FROM orders_status_history
 WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE 'PayPal App: Capture (%'
EOSQL
            , (int)$order['orders_id'], (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));
          while ( $c = $c_query->fetch_assoc() ) {
            if ( preg_match('/^PayPal App\: Capture \(([0-9\.]+)\)\n/', $c['comments'], $c_matches) ) {
              $capture_total -= $GLOBALS['currencies']->format_raw($c_matches[1], true, $order['currency'], 1);
            }
          }

          if ( $capture_total > 0 ) {
            $output .= $this->_app->drawButton($this->_app->getDef('button_dialog_void'), '#', 'warning', 'data-button="paypalButtonDoVoid"', true);

            $dialog_title = htmlspecialchars($this->_app->getDef('dialog_void_title'));
            $dialog_body = $this->_app->getDef('dialog_void_body');
            $void_link = Guarantor::ensure_global('Admin')->link('orders.php', [
              'page' => (int)($_GET['page'] ?? 1),
              'oID' => $order['orders_id'],
              'action' => 'edit',
              'tabaction' => 'doVoid',
            ]);
            $dialog_button_void = addslashes($this->_app->getDef('dialog_void_button_void'));
            $dialog_button_cancel = addslashes($this->_app->getDef('dialog_void_button_cancel'));

            $output .= <<<EOD
<div id="paypal-dialog-void" title="{$dialog_title}">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>{$dialog_body}</p>
</div>

<script>
$(function() {
  $('#paypal-dialog-void').dialog({
    autoOpen: false,
    resizable: false,
    modal: true,
    buttons: {
      "{$dialog_button_void}": function() {
        window.location = '{$void_link}';
      },
      "{$dialog_button_cancel}": function() {
        $(this).dialog('close');
      }
    }
  });

  $('a[data-button="paypalButtonDoVoid"]').click(function(e) {
    e.preventDefault();

    $('#paypal-dialog-void').dialog('open');
  });
});
</script>
EOD;
          }
        }
      }

      return $output;
    }

    function getRefundButton($status, $order) {
      $output = '';

      $tids = [];

      $ppr_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments
 FROM orders_status_history
 WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE 'PayPal App: %%'
 ORDER BY date_added DESC
EOSQL
        , (int)$_GET['oID'], (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));
      if ( mysqli_num_rows($ppr_query) ) {
        while ( $ppr = $ppr_query->fetch_assoc() ) {
          if ( strpos($ppr['comments'], 'PayPal App: Refund') !== false ) {
            preg_match('/Parent ID\: ([A-Za-z0-9]+)$/', $ppr['comments'], $ppr_matches);

            $tids[$ppr_matches[1]]['Refund'] = true;
          } elseif ( strpos($ppr['comments'], 'PayPal App: Capture') !== false ) {
            preg_match('/^PayPal App\: Capture \(([0-9\.]+)\).*Transaction ID\: ([A-Za-z0-9]+)/s', $ppr['comments'], $ppr_matches);

            $tids[$ppr_matches[2]]['Amount'] = $ppr_matches[1];
          }
        }
      } elseif ( $status['Payment Status'] == 'Completed' ) {
        $tids[$status['Transaction ID']]['Amount'] = $GLOBALS['currencies']->format_raw($order['total'], true, $order['currency'], $order['currency_value']);
      }

      $can_refund = false;

      foreach ( $tids as $value ) {
        if ( !isset($value['Refund']) ) {
          $can_refund = true;
          break;
        }
      }

      if ( $can_refund === true ) {
        $output .= $this->_app->drawButton($this->_app->getDef('button_dialog_refund'), '#', 'error', 'data-button="paypalButtonRefundTransaction"', true);

        $dialog_title = htmlspecialchars($this->_app->getDef('dialog_refund_title'));
        $dialog_body = $this->_app->getDef('dialog_refund_body');
        $refund_link = Guarantor::ensure_global('Admin')->link('orders.php', [
          'page' => (int)($_GET['page'] ?? 1),
          'oID' => (int)$_GET['oID'],
          'action' => 'edit',
          'tabaction' => 'refundTransaction',
        ]);
        $dialog_button_refund = addslashes($this->_app->getDef('dialog_refund_button_refund'));
        $dialog_button_cancel = addslashes($this->_app->getDef('dialog_refund_button_cancel'));

        $refund_fields = '';

        $counter = 0;

        foreach ( $tids as $key => $value ) {
          $refund_fields .= '<p><input type="checkbox" name="ppRefund[]" value="' . $key . '" id="ppRefundPartial' . $counter . '"' . (isset($value['Refund']) ? ' disabled="disabled"' : '') . ' /> <label for="ppRefundPartial' . $counter . '"' . (isset($value['Refund']) ? ' style="text-decoration: line-through;"' : '') . '>' . $this->_app->getDef('dialog_refund_payment_title', ['amount' => $value['Amount']]) . '</label></p>';

          $counter++;
        }

        $output .= <<<"EOD"
<div id="paypal-dialog-refund" title="{$dialog_title}">
  <form id="ppRefundForm" action="{$refund_link}" method="post">
    <p>{$dialog_body}</p>

    {$refund_fields}
  </form>
</div>

<script>
$(function() {
  $('#paypal-dialog-refund').dialog({
    autoOpen: false,
    resizable: false,
    modal: true,
    buttons: {
      "{$dialog_button_refund}": function() {
        $('#ppRefundForm').submit();
      },
      "{$dialog_button_cancel}": function() {
        $(this).dialog('close');
      }
    }
  });

  $('a[data-button="paypalButtonRefundTransaction"]').click(function(e) {
    e.preventDefault();

    $('#paypal-dialog-refund').dialog('open');
  });
});
</script>
EOD;
      }

      return $output;
    }

  }
