<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  if ( !class_exists('PayPal') ) {
    include DIR_FS_CATALOG . 'includes/apps/paypal/PayPal.php';
  }

  class paypal_hook_admin_orders_action {

    public function __construct() {
      global $PayPal;

      if ( !(($PayPal ?? null) instanceof PayPal) ) {
        $PayPal = new PayPal();
      }

      $this->_app = $PayPal;

      $this->_app->loadLanguageFile('hooks/admin/orders/action.php');
    }

    public function execute() {
      if ( isset($_GET['tabaction']) ) {
        $ppstatus_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments
 FROM orders_status_history
 WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE '%%Transaction ID:%%'
 ORDER BY date_added
 LIMIT 1
EOSQL
          , (int)$_GET['oID'], (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));
        if ( $ppstatus = $ppstatus_query->fetch_assoc() ) {
          $pp = [];
          foreach ( explode("\n", $ppstatus['comments']) as $s ) {
            if ( !empty($s) && (strpos($s, ':') !== false) ) {
              $entry = explode(':', $s, 2);

              $pp[trim($entry[0])] = trim($entry[1]);
            }
          }

          if ( isset($pp['Transaction ID']) ) {
            $o = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT o.orders_id, o.payment_method, o.currency, o.currency_value, ot.value AS total
 FROM orders o INNER JOIN orders_total ot ON o.orders_id = ot.orders_id
 WHERE o.orders_id = %d AND ot.class = 'ot_total'
EOSQL
              , (int)$_GET['oID']))->fetch_assoc();

            switch ( $_GET['tabaction'] ) {
              case 'getTransactionDetails':
                $this->getTransactionDetails($pp, $o);
                break;

              case 'doCapture':
                $this->doCapture($pp, $o);
                break;

              case 'doVoid':
                $this->doVoid($pp, $o);
                break;

              case 'refundTransaction':
                $this->refundTransaction($pp, $o);
                break;
            }

            Href::redirect(Guarantor::ensure_global('Admin')->link('orders.php', ['page' => $_GET['page'], 'oID' => $_GET['oID'], 'action' => 'edit']) . '#section_status_history_content');
          }
        }
      }
    }

    public function getTransactionDetails($comments, $order) {
      $result = null;
      if ( !isset($comments['Gateway']) ) {
        $response = $this->_app->getApiResult('APP', 'GetTransactionDetails', ['TRANSACTIONID' => $comments['Transaction ID']], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( in_array($response['ACK'], ['Success', 'SuccessWithWarning']) ) {
          $result = 'Transaction ID: ' . htmlspecialchars($response['TRANSACTIONID']) . "\n" .
                    'Payer Status: ' . htmlspecialchars($response['PAYERSTATUS']) . "\n" .
                    'Address Status: ' . htmlspecialchars($response['ADDRESSSTATUS']) . "\n" .
                    'Payment Status: ' . htmlspecialchars($response['PAYMENTSTATUS']) . "\n" .
                    'Payment Type: ' . htmlspecialchars($response['PAYMENTTYPE']) . "\n" .
                    'Pending Reason: ' . htmlspecialchars($response['PENDINGREASON']);
        }
      } elseif ( $comments['Gateway'] == 'Payflow' ) {
        $response = $this->_app->getApiResult('APP', 'PayflowInquiry', ['ORIGID' => $comments['Transaction ID']], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( isset($response['RESULT']) && ($response['RESULT'] == '0') ) {
          $result = 'Transaction ID: ' . htmlspecialchars($response['ORIGPNREF']) . "\n" .
                    'Gateway: Payflow' . "\n";

          $pending_reason = $response['TRANSSTATE'];
          $payment_status = null;

          switch ( $response['TRANSSTATE'] ) {
            case '3':
              $pending_reason = 'authorization';
              $payment_status = 'Pending';
              break;

            case '4':
              $pending_reason = 'other';
              $payment_status = 'In-Progress';
              break;

            case '6':
              $pending_reason = 'scheduled';
              $payment_status = 'Pending';
              break;

            case '8':
            case '9':
              $pending_reason = 'None';
              $payment_status = 'Completed';
              break;
          }

          if ( isset($payment_status) ) {
            $result .= 'Payment Status: ' . htmlspecialchars($payment_status) . "\n";
          }

          $result .= 'Pending Reason: ' . htmlspecialchars($pending_reason) . "\n";

          switch ( $response['AVSADDR'] ) {
            case 'Y':
              $result .= 'AVS Address: Match' . "\n";
              break;

            case 'N':
              $result .= 'AVS Address: No Match' . "\n";
              break;
          }

          switch ( $response['AVSZIP'] ) {
            case 'Y':
              $result .= 'AVS ZIP: Match' . "\n";
              break;

            case 'N':
              $result .= 'AVS ZIP: No Match' . "\n";
              break;
          }

          switch ( $response['IAVS'] ) {
            case 'Y':
              $result .= 'IAVS: International' . "\n";
              break;

            case 'N':
              $result .= 'IAVS: USA' . "\n";
              break;
          }

          switch ( $response['CVV2MATCH'] ) {
            case 'Y':
              $result .= 'CVV2: Match' . "\n";
              break;

            case 'N':
              $result .= 'CVV2: No Match' . "\n";
              break;
          }
        }
      }

      if ( $result ) {
        $sql_data = [
          'orders_id' => (int)$order['orders_id'],
          'orders_status_id' => PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID,
          'date_added' => 'NOW()',
          'customer_notified' => '0',
          'comments' => $result,
        ];

        $GLOBALS['db']->perform('orders_status_history', $sql_data);

        $GLOBALS['messageStack']->add_session($this->_app->getDef('ms_success_getTransactionDetails'), 'success');
      } else {
        $GLOBALS['messageStack']->add_session($this->_app->getDef('ms_error_getTransactionDetails'), 'error');
      }
    }

    public function doCapture($comments, $order) {
      $pass = false;

      $capture_total = $GLOBALS['currencies']->format_raw($order['total'], true, $order['currency'], $order['currency_value']);
      $capture_value = $GLOBALS['currencies']->format_raw($_POST['ppCaptureAmount'], true, $order['currency'], 1);

      if ( $capture_value < $capture_total ) {
        $capture_final = isset($_POST['ppCaptureComplete']) && ($_POST['ppCaptureComplete'] === 'true');
      } else {
        $capture_value = $capture_total;
        $capture_final = true;
      }

      if ( !isset($comments['Gateway']) ) {
        $params = [
          'AUTHORIZATIONID' => $comments['Transaction ID'],
          'AMT' => $capture_value,
          'CURRENCYCODE' => $order['currency'],
          'COMPLETETYPE' => ($capture_final === true) ? 'Complete' : 'NotComplete',
        ];

        $response = $this->_app->getApiResult('APP', 'DoCapture', $params, (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( in_array($response['ACK'], ['Success', 'SuccessWithWarning']) ) {
          $transaction_id = $response['TRANSACTIONID'];

          $pass = true;
        }
      } elseif ( $comments['Gateway'] == 'Payflow' ) {
        $params = [
          'ORIGID' => $comments['Transaction ID'],
          'AMT' => $capture_value,
          'CAPTURECOMPLETE' => ($capture_final === true) ? 'Y' : 'N',
        ];

        $response = $this->_app->getApiResult('APP', 'PayflowCapture', $params, (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( isset($response['RESULT']) && ($response['RESULT'] == '0') ) {
          $transaction_id = $response['PNREF'];

          $pass = true;
        }
      }

      if ( $pass === true ) {
        $result = 'PayPal App: Capture (' . $capture_value . ')' . "\n";

        if ( ($capture_value < $capture_total) && ($capture_final === true) ) {
          $result .= 'PayPal App: Void (' . $GLOBALS['currencies']->format_raw($capture_total - $capture_value, true, $order['currency'], 1) . ')' . "\n";
        }

        $result .= 'Transaction ID: ' . htmlspecialchars($transaction_id);

        $sql_data = [
          'orders_id' => (int)$order['orders_id'],
          'orders_status_id' => PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID,
          'date_added' => 'NOW()',
          'customer_notified' => '0',
          'comments' => $result,
        ];

        $GLOBALS['db']->perform('orders_status_history', $sql_data);

        $GLOBALS['messageStack']->add_session($this->_app->getDef('ms_success_doCapture'), 'success');
      } else {
        $GLOBALS['messageStack']->add_session($this->_app->getDef('ms_error_doCapture'), 'error');
      }
    }

    public function doVoid($comments, $order) {
      $pass = false;
      if ( !isset($comments['Gateway']) ) {
        $response = $this->_app->getApiResult('APP', 'DoVoid', ['AUTHORIZATIONID' => $comments['Transaction ID']], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( in_array($response['ACK'], ['Success', 'SuccessWithWarning']) ) {
          $pass = true;
        }
      } elseif ( $comments['Gateway'] == 'Payflow' ) {
        $response = $this->_app->getApiResult('APP', 'PayflowVoid', ['ORIGID' => $comments['Transaction ID']], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( isset($response['RESULT']) && ($response['RESULT'] == '0') ) {
          $pass = true;
        }
      }

      if ( $pass === true ) {
        $capture_total = $GLOBALS['currencies']->format_raw($order['total'], $order['currency'], $order['currency_value']);

        $c_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments FROM orders_status_history WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE 'PayPal App: Capture (%%'
EOSQL
          , (int)$order['orders_id'], (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));
        while ( $c = $c_query->fetch_assoc() ) {
          if ( preg_match('/^PayPal App\: Capture \(([0-9\.]+)\)\n/', $c['comments'], $c_matches) ) {
            $capture_total -= $GLOBALS['currencies']->format_raw($c_matches[1], $order['currency'], 1);
          }
        }

        $result = 'PayPal App: Void (' . $capture_total . ')';

        $sql_data = [
          'orders_id' => (int)$order['orders_id'],
          'orders_status_id' => PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID,
          'date_added' => 'NOW()',
          'customer_notified' => '0',
          'comments' => $result,
        ];

        $GLOBALS['db']->perform('orders_status_history', $sql_data);

        $GLOBALS['messageStack']->add_session($this->_app->getDef('ms_success_doVoid'), 'success');
      } else {
        $GLOBALS['messageStack']->add_session($this->_app->getDef('ms_error_doVoid'), 'error');
      }
    }

    public function refundTransaction($comments, $order) {
      if ( isset($_POST['ppRefund']) ) {
        $tids = [];

        $ppr_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT comments
 FROM orders_status_history
 WHERE orders_id = %d AND orders_status_id = %d AND comments LIKE 'PayPal App: %%'
 ORDER BY date_added DESC
EOSQL
          , (int)$order['orders_id'], (int)PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID));
        if ( mysqli_num_rows($ppr_query) ) {
          while ( $ppr = $ppr_query->fetch_assoc() ) {
            if ( strpos($ppr['comments'], 'PayPal App: Refund') !== false ) {
              preg_match('{Parent ID\: ([A-Za-z0-9]+)$}', $ppr['comments'], $ppr_matches);

              $tids[$ppr_matches[1]]['Refund'] = true;
            } elseif ( strpos($ppr['comments'], 'PayPal App: Capture') !== false ) {
              preg_match('{^PayPal App\: Capture \(([0-9\.]+)\).*Transaction ID\: ([A-Za-z0-9]+)}s', $ppr['comments'], $ppr_matches);

              $tids[$ppr_matches[2]]['Amount'] = $ppr_matches[1];
            }
          }
        } elseif ( $comments['Payment Status'] == 'Completed' ) {
          $tids[$comments['Transaction ID']]['Amount'] = $GLOBALS['currencies']->format_raw($order['total'], true, $order['currency'], $order['currency_value']);
        }

        $rids = [];

        foreach ( $_POST['ppRefund'] as $id ) {
          if ( isset($tids[$id]) && !isset($tids[$id]['Refund']) ) {
            $rids[] = $id;
          }
        }

        foreach ( $rids as $id ) {
          $pass = false;

          if ( !isset($comments['Gateway']) ) {
            $response = $this->_app->getApiResult('APP', 'RefundTransaction', ['TRANSACTIONID' => $id], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

            if ( in_array($response['ACK'], ['Success', 'SuccessWithWarning']) ) {
              $transaction_id = $response['REFUNDTRANSACTIONID'];

              $pass = true;
            }
          } elseif ( $comments['Gateway'] == 'Payflow' ) {
            $response = $this->_app->getApiResult('APP', 'PayflowRefund', ['ORIGID' => $id], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

            if ( isset($response['RESULT']) && ($response['RESULT'] == '0') ) {
              $transaction_id = $response['PNREF'];

              $pass = true;
            }
          }

          if ( $pass === true ) {
            $result = 'PayPal App: Refund (' . $tids[$id]['Amount'] . ')' . "\n" .
                      'Transaction ID: ' . htmlspecialchars($transaction_id) . "\n" .
                      'Parent ID: ' . htmlspecialchars($id);

            $sql_data_array = [
              'orders_id' => (int)$order['orders_id'],
              'orders_status_id' => PAYPAL_APP_TRANSACTIONS_ORDER_STATUS_ID,
              'date_added' => 'NOW()',
              'customer_notified' => '0',
              'comments' => $result,
            ];

            $GLOBALS['db']->perform('orders_status_history', $sql_data_array);

            $GLOBALS['messageStack']->add_session($this->_app->getDef('ms_success_refundTransaction', ['refund_amount' => $tids[$id]['Amount']]), 'success');
          } else {
            $GLOBALS['messageStack']->add_session($this->_app->getDef('ms_error_refundTransaction', ['refund_amount' => $tids[$id]['Amount']]), 'error');
          }
        }
      }
    }

  }
