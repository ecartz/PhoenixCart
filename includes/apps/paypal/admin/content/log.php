<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  $log_query_sql = <<<'EOSQL'
SELECT l.id, l.customers_id, l.module, l.action, l.result, l.ip_address, UNIX_TIMESTAMP(l.date_added) AS date_added, c.customers_firstname, c.customers_lastname
 FROM paypal_app_log l LEFT JOIN customers c ON (l.customers_id = c.customers_id)
 ORDER BY l.date_added DESC
EOSQL;
  $log_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $log_query_sql, $log_query_numrows);
  $log_query = $GLOBALS['db']->query($log_query_sql);
?>

<h1 class="display-4"><?= $PayPal->getDef('heading_log'); ?></h1>

<table class="table table-hover">
  <thead class="thead-dark">
    <tr>
      <th colspan="2"><?= $PayPal->getDef('table_heading_action'); ?></th>
      <th><?= $PayPal->getDef('table_heading_ip'); ?></th>
      <th><?= $PayPal->getDef('table_heading_customer'); ?></th>
      <th colspan="2"><?= $PayPal->getDef('table_heading_date'); ?></th>
    </tr>
  </thead>
  <tbody>
<?php
  if ( mysqli_num_rows($log_query) > 0 ) {
    while ($log = $log_query->fetch_assoc()) {
      if ( $log['customers_id'] > 0 ) {
        $customers_name = trim($log['customers_firstname'] . ' ' . $log['customers_lastname']);

        if ( empty($customers_name) ) {
          $customers_name = '- ? -';
        }
      } else {
        $customers_name = null;
      }
?>

    <tr>
      <td style="text-center"><span class="<?= ($log['result'] == '1') ? 'logSuccess' : 'logError'; ?>"><?= $log['module']; ?></span></td>
      <td><?= $log['action']; ?></td>
      <td><?= long2ip($log['ip_address']); ?></td>
      <td><?= empty($customers_name) ? '<i>' . $PayPal->getDef('guest') . '</i>' : htmlspecialchars($customers_name) ?></td>
      <td><?= date(PHP_DATE_TIME_FORMAT, $log['date_added']); ?></td>
      <td class="pp-table-action text-right"><?= $PayPal->drawButton($PayPal->getDef('button_view'), Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'log', 'page' => $_GET['page'], 'lID' => $log['id'], 'subaction' => 'view']), 'info'); ?></td>
    </tr>

<?php
    }
  } else {
?>

    <tr>
      <td colspan="6"><?= $PayPal->getDef('no_entries'); ?></td>
    </tr>

<?php
  }
?>

  </tbody>
</table>

<div class="row my-1">
  <div class="col"><?= $log_split->display_count($log_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], $PayPal->getDef('listing_number_of_log_entries')); ?></div>
  <div class="col text-right mr-2"><?= $log_split->display_links($log_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], 'action=log'); ?></div>
</div>
