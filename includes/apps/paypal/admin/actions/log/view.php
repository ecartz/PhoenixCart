<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  if ( isset($_GET['lID']) && is_numeric($_GET['lID']) ) {
    $log_query = $GLOBALS['db']->query(sprintf(<<<'EOSQL'
SELECT l.*, UNIX_TIMESTAMP(l.date_added) AS date_added, c.customers_firstname, c.customers_lastname
 FROM paypal_app_log l LEFT JOIN customers c ON l.customers_id = c.customers_id
 WHERE l.id = %d
EOSQL
      , (int)$_GET['lID']));

    if ( $log = $log_query->fetch_assoc() ) {
      $log_request = [];
      foreach ( explode("\n", $log['request']) as $r ) {
        $p = explode(':', $r, 2);

        $log_request[$p[0]] = $p[1];
      }

      $log_response = [];
      foreach ( explode("\n", $log['response']) as $r ) {
        $p = explode(':', $r, 2);

        $log_response[$p[0]] = $p[1];
      }

      $content = 'log_view.php';
    }
  }
