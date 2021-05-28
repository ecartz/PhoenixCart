<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  chdir('../../../../');
  require 'includes/application_top.php';

// initialize variables if the customer is not logged in
  $customer_id = $_SESSION['customer_id'] ?? 0;

  $paypal_express = new paypal_express();

  if ( !$paypal_express->check() || !$paypal_express->enabled ) {
    Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));
  }

  require language::map_to_translation('create_account.php');

  if ( !isset($_SESSION['sendto']) ) {
    if ( isset($_SESSION['customer_id']) ) {
      $_SESSION['sendto'] = $customer->get('default_sendto');
    } else {
      $country = [ 'country' => [ 'id' => STORE_COUNTRY ] ];
      $country = $customer_data->get('country', $country);

      $_SESSION['sendto'] = [
        'firstname' => '',
        'lastname' => '',
        'name' => '',
        'company' => '',
        'street_address' => '',
        'suburb' => '',
        'postcode' => '',
        'city' => '',
        'zone_id' => STORE_ZONE,
        'zone_name' => Zone::fetch_name(STORE_COUNTRY, STORE_ZONE, ''),
        'country_id' => STORE_COUNTRY,
        'country_name' => $country['name'],
        'country_iso_code_2' => $country['iso_code_2'],
        'country_iso_code_3' => $country['iso_code_3'],
        'address_format_id' => $country['format_id'],
      ];
    }
  }

  if ( !isset($_SESSION['billto']) ) {
    $_SESSION['billto'] = $_SESSION['sendto'];
  }

// register a random ID in the session to check throughout the checkout procedure
// against alterations in the shopping cart contents
  $_SESSION['cartID'] = $_SESSION['cart']->cartID;

  switch ($_GET['action'] ?? '') {
    case 'cancel':
      unset($_SESSION['appPayPalEcResult']);
      unset($_SESSION['appPayPalEcSecret']);

      if ( empty($_SESSION['sendto']['firstname']) && empty($_SESSION['sendto']['lastname']) && empty($_SESSION['sendto']['street_address']) ) {
        unset($_SESSION['sendto']);
      }

      if ( empty($_SESSION['billto']['firstname']) && empty($_SESSION['billto']['lastname']) && empty($_SESSION['billto']['street_address']) ) {
        unset($_SESSION['billto']);
      }

      Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));

      break;
    case 'callbackSet':
      if ( (PAYPAL_APP_GATEWAY == '1') && (PAYPAL_APP_EC_INSTANT_UPDATE == '1') ) {
        $log_sane = [];

        $counter = 0;

        if (isset($_POST['CURRENCYCODE']) && $currencies->is_set($_POST['CURRENCYCODE']) && ($_SESSION['currency'] != $_POST['CURRENCYCODE'])) {
          $_SESSION['currency'] = $_POST['CURRENCYCODE'];

          $log_sane['CURRENCYCODE'] = $_POST['CURRENCYCODE'];
        }

        while (true) {
          if ( isset($_POST['L_NUMBER' . $counter], $_POST['L_QTY' . $counter]) ) {
            $_SESSION['cart']->add_cart($_POST['L_NUMBER' . $counter], $_POST['L_QTY' . $counter]);

            $log_sane['L_NUMBER' . $counter] = $_POST['L_NUMBER' . $counter];
            $log_sane['L_QTY' . $counter] = $_POST['L_QTY' . $counter];
          } else {
            break;
          }

          $counter++;
        }

// exit if there is nothing in the shopping cart
        if ($_SESSION['cart']->count_contents() < 1) {
          exit;
        }

        $_SESSION['sendto'] = [
          'firstname' => '',
          'lastname' => '',
          'company' => '',
          'street_address' => $_POST['SHIPTOSTREET'],
          'suburb' => $_POST['SHIPTOSTREET2'],
          'postcode' => $_POST['SHIPTOZIP'],
          'city' => $_POST['SHIPTOCITY'],
          'zone_id' => '',
          'zone_name' => $_POST['SHIPTOSTATE'],
          'country_id' => '',
          'country_name' => $_POST['SHIPTOCOUNTRY'],
          'country_iso_code_2' => '',
          'country_iso_code_3' => '',
          'address_format_id' => '',
        ];

        $log_sane['SHIPTOSTREET'] = $_POST['SHIPTOSTREET'];
        $log_sane['SHIPTOSTREET2'] = $_POST['SHIPTOSTREET2'];
        $log_sane['SHIPTOZIP'] = $_POST['SHIPTOZIP'];
        $log_sane['SHIPTOCITY'] = $_POST['SHIPTOCITY'];
        $log_sane['SHIPTOSTATE'] = $_POST['SHIPTOSTATE'];
        $log_sane['SHIPTOCOUNTRY'] = $_POST['SHIPTOCOUNTRY'];

        $country_query = $db->query("SELECT * FROM countries WHERE countries_iso_code_2 = '" . $db->escape($_SESSION['sendto']['country_name']) . "' LIMIT 1");
        if ($country = $country_query->fetch_assoc()) {
          $_SESSION['sendto']['country_id'] = $country['countries_id'];
          $_SESSION['sendto']['country_name'] = $country['countries_name'];
          $_SESSION['sendto']['country_iso_code_2'] = $country['countries_iso_code_2'];
          $_SESSION['sendto']['country_iso_code_3'] = $country['countries_iso_code_3'];
          $_SESSION['sendto']['address_format_id'] = $country['address_format_id'];
        }

        if ($_SESSION['sendto']['country_id'] > 0) {
          $zone_query = $db->query("select * from zones where zone_country_id = '" . (int)$_SESSION['sendto']['country_id'] . "' and (zone_name = '" . $db->escape($_SESSION['sendto']['zone_name']) . "' or zone_code = '" . $db->escape($_SESSION['sendto']['zone_name']) . "') limit 1");
          if ($zone = $zone_query->fetch_assoc()) {
            $_SESSION['sendto']['zone_id'] = $zone['zone_id'];
            $_SESSION['sendto']['zone_name'] = $zone['zone_name'];
          }
        }

        $_SESSION['billto'] = $_SESSION['sendto'];


        $order = new order();

        if ($_SESSION['cart']->get_content_type() === 'virtual') {
          $quotes = [[
            'id' => 'null',
            'name' => 'No Shipping',
            'label' => '',
            'cost' => '0',
            'tax' => '0',
          ]];
        } else {
          $quotes = [];
          $total_weight = $_SESSION['cart']->show_weight();
          $total_count = $_SESSION['cart']->count_contents();

// load all enabled shipping modules
          $shipping_modules = new shipping();

          if ( ot_shipping::is_eligible_free_shipping($customer_data->get('country_id', $order->delivery), $order->info['total']) ) {
            include language::map_to_translation('modules/order_total/ot_shipping.php');

            $quotes[] = [
              'id' => 'free_free',
              'name' => FREE_SHIPPING_TITLE,
              'label' => '',
              'cost' => '0',
              'tax' => '0',
            ];
          } elseif ( $shipping_modules->count() > 0 ) {
// get all available shipping quotes
            foreach ($shipping_modules->quote() as $quote) {
              if (isset($quote['error'])) {
                continue;
              }

              foreach ($quote['methods'] as $rate) {
                $quotes[] = [
                  'id' => $quote['id'] . '_' . $rate['id'],
                  'name' => $quote['module'],
                  'label' => $rate['title'],
                  'cost' => $rate['cost'],
                  'tax' => ($quote['tax'] ?? '0'),
                ];
              }
            }
          }
        }

        $order_total_modules = new order_total();
        $order->totals = $order_total_modules->process();

        $params = [
          'METHOD' => 'CallbackResponse',
          'CALLBACKVERSION' => $paypal_express->api_version,
        ];

        if ( empty($quotes) ) {
          $params['NO_SHIPPING_OPTION_DETAILS'] = '1';
        } else {
          $params['CURRENCYCODE'] = $_SESSION['currency'];
          $params['OFFERINSURANCEOPTION'] = 'false';

          $counter = 0;
          $cheapest_rate = null;
          $cheapest_counter = $counter;

          foreach ($quotes as $quote) {
            $shipping_rate = $GLOBALS['currencies']->format_raw($quote['cost'] + Tax::calculate($quote['cost'], $quote['tax']));

            $params['L_SHIPPINGOPTIONNAME' . $counter] = $quote['name'];
            $params['L_SHIPPINGOPTIONLABEL' . $counter] = $quote['label'];
            $params['L_SHIPPINGOPTIONAMOUNT' . $counter] = $shipping_rate;
            $params['L_SHIPPINGOPTIONISDEFAULT' . $counter] = 'false';

            if ( DISPLAY_PRICE_WITH_TAX == 'false' ) {
              $params['L_TAXAMT' . $counter] = $GLOBALS['currencies']->format_raw($order->info['tax']);
            }

            if (is_null($cheapest_rate) || ($shipping_rate < $cheapest_rate)) {
              $cheapest_rate = $shipping_rate;
              $cheapest_counter = $counter;
            }

            $counter++;
          }

          if ( method_exists($shipping_modules, 'get_first') ) { // select first shipping method
            $params['L_SHIPPINGOPTIONISDEFAULT0'] = 'true';
          } else { // select cheapest shipping method
            $params['L_SHIPPINGOPTIONISDEFAULT' . $cheapest_counter] = 'true';
          }
        }

        $post_string = '';

        foreach ($params as $key => $value) {
          $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
        }

        $post_string = substr($post_string, 0, -1);

        $paypal_express->_app->log('EC', 'CallbackResponse', 1, $log_sane, $params);

        echo $post_string;
      }

      Session::destroy();

      exit();
    case 'retrieve':
      if ( ($_SESSION['cart']->count_contents() < 1) || empty($_GET['token']) || !isset($_SESSION['appPayPalEcSecret']) ) {
        Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));
      }

      if ( !isset($_SESSION['appPayPalEcResult']) || ($appPayPalEcResult['TOKEN'] != $_GET['token']) ) {
        if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
          $_SESSION['appPayPalEcResult'] = $paypal_express->_app->getApiResult('EC', 'GetExpressCheckoutDetails', ['TOKEN' => $_GET['token']]);
        } else { // Payflow
          $_SESSION['appPayPalEcResult'] = $paypal_express->_app->getApiResult('EC', 'PayflowGetExpressCheckoutDetails', ['TOKEN' => $_GET['token']]);
        }
        $appPayPalEcResult = &$_SESSION['appPayPalEcResult'];
      }

      $pass = false;

      if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
        if ( in_array($appPayPalEcResult['ACK'], ['Success', 'SuccessWithWarning']) ) {
          $pass = true;
        }
      } else { // Payflow
        if ( $appPayPalEcResult['RESULT'] == '0' ) {
          $pass = true;
        }
      }

      if ( $pass === true ) {
        if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
          if ( $appPayPalEcResult['PAYMENTREQUEST_0_CUSTOM'] != $_SESSION['appPayPalEcSecret'] ) {
            Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));
          }
        } else { // Payflow
          if ( $appPayPalEcResult['CUSTOM'] != $_SESSION['appPayPalEcSecret'] ) {
            Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));
          }
        }

        $_SESSION['payment'] = $paypal_express->code;

        $force_login = false;

// check if e-mail address exists in database and login or create customer account
        if ( !isset($_SESSION['customer_id']) ) {
          $force_login = true;

          $email_address = Text::input($appPayPalEcResult['EMAIL']);

          $check_query = $db->query("SELECT * FROM customers WHERE customers_email_address = '" . $db->escape($email_address) . "' LIMIT 1");
          if ( $check = $check_query->fetch_assoc() ) {
// Force the customer to log into their local account if payerstatus is unverified and a local password is set
            if ( ($appPayPalEcResult['PAYERSTATUS'] == 'unverified') && !empty($check['customers_password']) ) {
              $messageStack->add_session('login', $paypal_express->_app->getDef('module_ec_error_local_login_required'), 'warning');

              $_SESSION['navigation']->set_snapshot();

              $login_url = $GLOBALS['Linker']->build('login.php');
              $login_email_address = Text::output($appPayPalEcResult['EMAIL']);

      $output = <<<EOD
<form name="pe" action="{$login_url}" method="post" target="_top">
  <input type="hidden" name="email_address" value="{$login_email_address}" />
</form>
<script type="text/javascript">
document.pe.submit();
</script>
EOD;

              echo $output;
              exit();
            } else {
              $customer_id = $_SESSION['customer_id'] = $check['customers_id'];
              $customers_firstname = $check['customers_firstname'];
            }
          } else {
            $customers_firstname = Text::input($appPayPalEcResult['FIRSTNAME']);
            $customers_lastname = Text::input($appPayPalEcResult['LASTNAME']);

            $sql_data = [
              'customers_firstname' => $customers_firstname,
              'customers_lastname' => $customers_lastname,
              'customers_email_address' => $email_address,
              'customers_telephone' => '',
              'customers_fax' => '',
              'customers_newsletter' => '0',
              'customers_password' => '',
              'customers_gender' => '',
            ];

            if ( isset($appPayPalEcResult['PHONENUM']) && !Text::is_empty($appPayPalEcResult['PHONENUM']) ) {
              $customers_telephone = Text::input($appPayPalEcResult['PHONENUM']);

              $sql_data['customers_telephone'] = $customers_telephone;
            }

            $db->perform('customers', $sql_data);

            $_SESSION['customer_id'] = mysqli_insert_id($db);
            $customer_id = $_SESSION['customer_id'];

            $db->query("INSERT INTO customers_info (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) VALUES (" . (int)$_SESSION['customer_id'] . ", '0', NOW())");

// Only generate a password and send an email if the Set Password Content Module is not enabled
            if ( !defined('MODULE_CONTENT_ACCOUNT_SET_PASSWORD_STATUS') || (MODULE_CONTENT_ACCOUNT_SET_PASSWORD_STATUS != 'True') ) {
              $customer_password = Password::create_random(max(ENTRY_PASSWORD_MIN_LENGTH, 8));

              $db->perform('customers', ['customers_password' => $customer_password], 'update', 'customers_id = ' . (int)$_SESSION['customer_id']);

// build the message content
              $name = $customers_firstname . ' ' . $customers_lastname;
              $email_text = sprintf(EMAIL_GREET_NONE, $customers_firstname) . EMAIL_WELCOME . $paypal_express->_app->getDef('module_ec_email_account_password', ['email_address' => $email_address, 'password' => $customer_password]) . "\n\n" . EMAIL_TEXT . EMAIL_CONTACT . EMAIL_WARNING;
              Notifications::mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
          }

          $hooks->cat('postRegistration');

          $customer = new customer($customer_id);
        }

// check if paypal shipping address exists in the address book
        if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
          $ship_names = explode(' ', $appPayPalEcResult['PAYMENTREQUEST_0_SHIPTONAME'] ?? ' ', 2);
          $ship_address = Text::input($appPayPalEcResult['PAYMENTREQUEST_0_SHIPTOSTREET'] ?? '');
          $ship_suburb = Text::input($appPayPalEcResult['PAYMENTREQUEST_0_SHIPTOSTREET2'] ?? '');
          $ship_city = Text::input($appPayPalEcResult['PAYMENTREQUEST_0_SHIPTOCITY'] ?? '');
          $ship_zone = Text::input($appPayPalEcResult['PAYMENTREQUEST_0_SHIPTOSTATE'] ?? '');
          $ship_postcode = Text::input($appPayPalEcResult['PAYMENTREQUEST_0_SHIPTOZIP'] ?? '');
          $ship_country = Text::input($appPayPalEcResult['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] ?? '');
        } else { // Payflow
          $ship_names = explode(' ', $appPayPalEcResult['SHIPTONAME'] ?? ' ', 2);
          $ship_address = Text::input($appPayPalEcResult['SHIPTOSTREET'] ?? '');
          $ship_suburb = Text::input($appPayPalEcResult['SHIPTOSTREET2'] ?? '');
          $ship_city = Text::input($appPayPalEcResult['SHIPTOCITY'] ?? '');
          $ship_zone = Text::input($appPayPalEcResult['SHIPTOSTATE'] ?? '');
          $ship_postcode = Text::input($appPayPalEcResult['SHIPTOZIP'] ?? '');
          $ship_country = Text::input($appPayPalEcResult['SHIPTOCOUNTRY'] ?? '');
        }

        $ship_firstname = Text::input($ship_names[0] ?? '');
        $ship_lastname = Text::input($ship_names[1] ?? '');

        $ship_zone_id = 0;
        $ship_country_id = 0;
        $ship_address_format_id = 1;

        $country_query = $db->query("SELECT countries_id, address_format_id FROM countries WHERE countries_iso_code_2 = '" . $db->escape($ship_country) . "' LIMIT 1");
        if ( $country = $country_query->fetch_assoc() ) {
          $ship_country_id = $country['countries_id'];
          $ship_address_format_id = $country['address_format_id'];
        }

        if ( $ship_country_id > 0 ) {
          $zone_query = $db->query(sprintf(<<<'EOSQL'
SELECT zone_id FROM zones WHERE zone_country_id = %1$d AND (zone_name = '%2$s' OR zone_code = '%2$s') LIMIT 1
EOSQL
            , (int)$ship_country_id, $db->escape($ship_zone)));
          if ($zone = $zone_query->fetch_assoc()) {
            $ship_zone_id = $zone['zone_id'];
          }
        }

        $check_query = $db->query(sprintf(<<<'EOSQL'
SELECT address_book_id
 FROM address_book
 WHERE customers_id = %d AND entry_firstname = '%s' AND entry_lastname = '%s'
   AND entry_street_address = '%s' AND entry_suburb = '%s' AND entry_postcode = '%s'
   AND entry_city = '%s' AND (entry_state = '%s' OR entry_zone_id = %d) AND entry_country_id = %d
 LIMIT 1
EOSQL
          , (int)$customer_id, $db->escape($ship_firstname), $db->escape($ship_lastname),
          $db->escape($ship_address), $db->escape($ship_suburb), $db->escape($ship_postcode),
          $db->escape($ship_city), $db->escape($ship_zone), (int)$ship_zone_id, (int)$ship_country_id));
        if ( $check = $check_query->fetch_assoc() ) {
          $_SESSION['sendto'] = $check['address_book_id'];
        } else {
          $sql_data = [
            'customers_id' => $customer_id,
            'entry_firstname' => $ship_firstname,
            'entry_lastname' => $ship_lastname,
            'entry_street_address' => $ship_address,
            'entry_suburb' => $ship_suburb,
            'entry_postcode' => $ship_postcode,
            'entry_city' => $ship_city,
            'entry_country_id' => $ship_country_id,
            'entry_gender' => '',
          ];

          if ($customer_data->has(['state'])) {
            if ($ship_zone_id > 0) {
              $sql_data['entry_zone_id'] = $ship_zone_id;
              $sql_data['entry_state'] = '';
            } else {
              $sql_data['entry_zone_id'] = '0';
              $sql_data['entry_state'] = $ship_zone;
            }
          }

          $db->perform('address_book', $sql_data);

          $address_id = mysqli_insert_id($db);

          $_SESSION['sendto'] = $address_id;

          if ($customer->get('default_address_id') < 1) {
            $db->query("update customers set customers_default_address_id = '" . (int)$address_id . "' where customers_id = '" . (int)$customer_id . "'");
          }
        }

        $_SESSION['billto'] = $_SESSION['sendto'];

        $order = new order();

        if ($_SESSION['cart']->get_content_type() != 'virtual') {
          $total_weight = $_SESSION['cart']->show_weight();
          $total_count = $_SESSION['cart']->count_contents();

// load all enabled shipping modules
          $shipping_modules = new shipping();

          $_SESSION['shipping'] = false;

          if ( ot_shipping::is_eligible_free_shipping($customer_data->get('country_id', $order->delivery), $order->info['total']) ) {
            include language::map_to_translation('modules/order_total/ot_shipping.php');

            $_SESSION['shipping'] = 'free_free';
          } elseif ( $shipping_modules->count() > 0 ) {
            $shipping_set = false;

// if available, set the selected shipping rate from PayPal's order review page
            // Live server requires SSL to be enabled
            if ( (PAYPAL_APP_GATEWAY == '1')
              && (PAYPAL_APP_EC_INSTANT_UPDATE == '1')
              && ((PAYPAL_APP_EC_STATUS == '0') || ((PAYPAL_APP_EC_STATUS == '1') && ('SSL' === $GLOBALS['request_type'])))
              && (PAYPAL_APP_EC_CHECKOUT_FLOW == '0')
              && isset($appPayPalEcResult['SHIPPINGOPTIONNAME'], $appPayPalEcResult['SHIPPINGOPTIONAMOUNT']))
            {
// get all available shipping quotes
              foreach ($shipping_modules->quote() as $quote) {
                if (isset($quote['error'])) {
                  continue;
                }

                foreach ($quote['methods'] as $rate) {
                  if ($appPayPalEcResult['SHIPPINGOPTIONNAME'] == trim($quote['module'] . ' ' . $rate['title'])) {
                    $shipping_rate = $GLOBALS['currencies']->format_raw($rate['cost'] + Tax::calculate($rate['cost'], $quote['tax']));

                    if ($appPayPalEcResult['SHIPPINGOPTIONAMOUNT'] == $shipping_rate) {
                      $_SESSION['shipping'] = $quote['id'] . '_' . $rate['id'];
                      $shipping_set = true;
                      break 2;
                    }
                  }
                }
              }
            }

            if (!$shipping_set) {
              if ( method_exists($shipping_modules, 'get_first') ) { // select first shipping method
                $_SESSION['shipping'] = $shipping_modules->get_first();
              } else { // select cheapest shipping method
                $_SESSION['shipping'] = $shipping_modules->cheapest();
              }

              $_SESSION['shipping'] = $_SESSION['shipping']['id'];
            }
          } elseif ( defined('SHIPPING_ALLOW_UNDEFINED_ZONES') && (SHIPPING_ALLOW_UNDEFINED_ZONES == 'False') ) {
            unset($_SESSION['shipping']);

            $messageStack->add_session('checkout_address', $paypal_express->_app->getDef('module_ec_error_no_shipping_available'), 'error');

            $_SESSION['appPayPalEcRightTurn'] = true;

            Href::redirect($GLOBALS['Linker']->build('checkout_shipping_address.php'));
          }

          if (strpos($_SESSION['shipping'], '_')) {
            list($module, $method) = explode('_', $_SESSION['shipping']);

            if ( is_object($$module) || ('free_free' === $_SESSION['shipping']) ) {
              if ('free_free' === $_SESSION['shipping']) {
                $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
                $quote[0]['methods'][0]['cost'] = '0';
              } else {
                $quote = $shipping_modules->quote($method, $module);
              }

              if (isset($quote['error'])) {
                unset($_SESSION['shipping']);

                Href::redirect($GLOBALS['Linker']->build('checkout_shipping.php'));
              } elseif ( (isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost'])) ) {
                $_SESSION['shipping'] = [
                  'id' => $_SESSION['shipping'],
                  'title' => (('free_free' === $_SESSION['shipping']) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' ' . $quote[0]['methods'][0]['title']),
                  'cost' => $quote[0]['methods'][0]['cost'],
                ];
              }
            }
          }
        } else {
          $_SESSION['shipping'] = false;
          $_SESSION['sendto'] = false;
        }

        if ( isset($_SESSION['shipping']) ) {
          Href::redirect($GLOBALS['Linker']->build('checkout_confirmation.php'));
        } else {
          $_SESSION['appPayPalEcRightTurn'] = true;

          Href::redirect($GLOBALS['Linker']->build('checkout_shipping.php'));
        }
      } else {
        if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
          $messageStack->add_session('header', stripslashes($appPayPalEcResult['L_LONGMESSAGE0']), 'error');
        } else { // Payflow
          $messageStack->add_session('header', $appPayPalEcResult['PHOENIX_ERROR_MESSAGE'], 'error');
        }

        Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));
      }

      break;

    default:
// if there is nothing in the customer's cart, redirect to the shopping cart page
      if ( $_SESSION['cart']->count_contents() < 1 ) {
        Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));
      }

      if ( PAYPAL_APP_EC_STATUS == '1' ) {
        $paypal_url = 'https://www.paypal.com/';
      } else {
        $paypal_url = 'https://www.sandbox.paypal.com/';
      }

      if ( (PAYPAL_APP_GATEWAY == '1') && (PAYPAL_APP_EC_CHECKOUT_FLOW == '1') ) {
        $paypal_url .= 'checkoutnow?';
      } else {
        $paypal_url .= 'cgi-bin/webscr?cmd=_express-checkout&';
      }

      if (!isset($customer)) {
        $customer = new class {

          public function fetch_to_address($to = null) {
            return [];
          }

          public function get($key, $to = 0) {
            return null;
          }

        };
      }
      $order = new order();

      $params = [];

      if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
        $params['PAYMENTREQUEST_0_CURRENCYCODE'] = $order->info['currency'];
      } else { // Payflow
        $params['CURRENCY'] = $order->info['currency'];
        $params['EMAIL'] = $order->customer['email_address'];

        $customer_data->get('country', $order->billing);
        $params['BILLTOFIRSTNAME'] = $customer_data->get('firstname', $order->billing);
        $params['BILLTOLASTNAME'] = $customer_data->get('lastname', $order->billing);
        $params['BILLTOSTREET'] = $customer_data->get('street_address', $order->billing);
        $params['BILLTOSTREET2'] = $customer_data->get('suburb', $order->billing);
        $params['BILLTOCITY'] = $customer_data->get('city', $order->billing);
        $params['BILLTOSTATE'] = Zone::fetch_code(
          $customer_data->get('country_id', $order->billing),
          $customer_data->get('zone_id', $order->billing),
          $customer_data->get('state', $order->billing));
        $params['BILLTOCOUNTRY'] = $customer_data->get('country_iso_code_2', $order->billing);
        $params['BILLTOZIP'] = $customer_data->get('postcode', $order->billing);
      }

// A billing address is required for digital orders so we use the shipping address PayPal provides
//      if ($order->content_type == 'virtual') {
//        $params['NOSHIPPING'] = '1';
//      }

      $item_params = [];

      $line_item_no = 0;

      foreach ( $order->products as $product ) {
        if ( DISPLAY_PRICE_WITH_TAX == 'true' ) {
          $product_price = $GLOBALS['currencies']->format_raw($product['final_price'] + Tax::calculate($product['final_price'], $product['tax']));
        } else {
          $product_price = $GLOBALS['currencies']->format_raw($product['final_price']);
        }

        if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
          $item_params['L_PAYMENTREQUEST_0_NAME' . $line_item_no] = $product['name'];
          $item_params['L_PAYMENTREQUEST_0_AMT' . $line_item_no] = $product_price;
          $item_params['L_PAYMENTREQUEST_0_NUMBER' . $line_item_no] = $product['id'];
          $item_params['L_PAYMENTREQUEST_0_QTY' . $line_item_no] = $product['qty'];
          $item_params['L_PAYMENTREQUEST_0_ITEMURL' . $line_item_no] = $GLOBALS['Linker']->build('product_info.php', ['products_id' => $product['id']]);

          if ( (DOWNLOAD_ENABLED == 'true') && isset($product['attributes']) ) {
            $item_params['L_PAYMENTREQUEST_0_ITEMCATEGORY' . $line_item_no] = $paypal_express->getProductType($product['id'], $product['attributes']);
          } else {
            $item_params['L_PAYMENTREQUEST_0_ITEMCATEGORY' . $line_item_no] = 'Physical';
          }
        } else { // Payflow
          $item_params['L_NAME' . $line_item_no] = $product['name'];
          $item_params['L_COST' . $line_item_no] = $product_price;
          $item_params['L_QTY' . $line_item_no] = $product['qty'];
        }

        $line_item_no++;
      }

      if ( !Text::is_empty($customer_data->get('street_address', $order->delivery)) ) {
        if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
          $params['PAYMENTREQUEST_0_SHIPTONAME'] = $customer_data->get('name', $order->delivery);
          $params['PAYMENTREQUEST_0_SHIPTOSTREET'] = $customer_data->get('street_address', $order->delivery);
          $params['PAYMENTREQUEST_0_SHIPTOSTREET2'] = $customer_data->get('suburb', $order->delivery);
          $params['PAYMENTREQUEST_0_SHIPTOCITY'] = $customer_data->get('city', $order->delivery);
          $params['PAYMENTREQUEST_0_SHIPTOSTATE'] = Zone::fetch_code(
            $customer_data->get('country_id', $order->delivery),
            $customer_data->get('zone_id', $order->delivery),
            $customer_data->get('state', $order->delivery));
          $params['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $customer_data->get('country_iso_code_2', $order->delivery);
          $params['PAYMENTREQUEST_0_SHIPTOZIP'] = $customer_data->get('postcode', $order->delivery);
        } else { // Payflow
          $params['SHIPTONAME'] = $customer_data->get('name', $order->delivery);
          $params['SHIPTOSTREET'] = $customer_data->get('street_address', $order->delivery);
          $params['SHIPTOSTREET2'] = $customer_data->get('suburb', $order->delivery);
          $params['SHIPTOCITY'] = $customer_data->get('city', $order->delivery);
          $params['SHIPTOSTATE'] = Zone::fetch_code(
            $customer_data->get('country_id', $order->delivery),
            $customer_data->get('zone_id', $order->delivery),
            $customer_data->get('state', $order->delivery));
          $params['SHIPTOCOUNTRY'] = $customer_data->get('country_iso_code_2', $order->delivery);
          $params['SHIPTOZIP'] = $customer_data->get('postcode', $order->delivery);
        }
      }

      $paypal_item_total = $GLOBALS['currencies']->format_raw($order->info['subtotal']);

      // Live server requires SSL to be enabled
      if ( (PAYPAL_APP_GATEWAY == '1')
        && (PAYPAL_APP_EC_INSTANT_UPDATE == '1')
        && ((PAYPAL_APP_EC_STATUS == '0')
          || ((PAYPAL_APP_EC_STATUS == '1') && ('SSL' === $GLOBALS['request_type'])))
        && (PAYPAL_APP_EC_CHECKOUT_FLOW == '0') )
      {
        $quotes = [];

        if ( $_SESSION['cart']->get_content_type() != 'virtual' ) {
          $total_weight = $_SESSION['cart']->show_weight();
          $total_count = $_SESSION['cart']->count_contents();

// load all enabled shipping modules
          $shipping_modules = new shipping();

          if ( ot_shipping::is_eligible_free_shipping($customer_data->get('country_id', $order->delivery), $order->info['total']) ) {
            include language::map_to_translation('modules/order_total/ot_shipping.php');
            $quotes[] = [
              'id' => 'free_free',
              'name' => FREE_SHIPPING_TITLE,
              'label' => '',
              'cost' => '0.00',
              'tax' => '0',
            ];
          } elseif ( $shipping_modules->count() > 0 ) {
// get all available shipping quotes
              foreach ($shipping_modules->quote() as $quote) {
                if (!isset($quote['error'])) {
                  foreach ($quote['methods'] as $rate) {
                    $quotes[] = [
                      'id' => $quote['id'] . '_' . $rate['id'],
                      'name' => $quote['module'],
                      'label' => $rate['title'],
                      'cost' => $rate['cost'],
                      'tax' => ($quote['tax'] ?? null),
                    ];
                }
              }
            }
          } elseif ( defined('SHIPPING_ALLOW_UNDEFINED_ZONES') && (SHIPPING_ALLOW_UNDEFINED_ZONES == 'False') ) {
            unset($_SESSION['shipping']);

            $messageStack->add_session('checkout_address', $paypal_express->_app->getDef('module_ec_error_no_shipping_available'), 'error');

            Href::redirect($GLOBALS['Linker']->build('checkout_shipping_address.php'));
          }
        }

        $counter = 0;
        $cheapest_rate = null;
        $expensive_rate = 0;
        $cheapest_counter = $counter;
        $default_shipping = null;

        foreach ($quotes as $quote) {
          $shipping_rate = $GLOBALS['currencies']->format_raw($quote['cost'] + Tax::calculate($quote['cost'], $quote['tax']));

          $item_params['L_SHIPPINGOPTIONNAME' . $counter] = trim($quote['name'] . ' ' . $quote['label']);
          $item_params['L_SHIPPINGOPTIONAMOUNT' . $counter] = $shipping_rate;
          $item_params['L_SHIPPINGOPTIONISDEFAULT' . $counter] = 'false';

          if (is_null($cheapest_rate) || ($shipping_rate < $cheapest_rate)) {
            $cheapest_rate = $shipping_rate;
            $cheapest_counter = $counter;
          }

          if ($shipping_rate > $expensive_rate) {
            $expensive_rate = $shipping_rate;
          }

          if (isset($_SESSION['shipping']) && ($_SESSION['shipping']['id'] == $quote['id'])) {
            $default_shipping = $counter;
          }

          $counter++;
        }

        if ( !isset($default_shipping) && !empty($quotes) ) {
          if ( method_exists($shipping_modules, 'get_first') ) { // select first shipping method
            $cheapest_counter = 0;
          }

          $_SESSION['shipping'] = [
            'id' => $quotes[$cheapest_counter]['id'],
            'title' => $item_params['L_SHIPPINGOPTIONNAME' . $cheapest_counter],
            'cost' => $GLOBALS['currencies']->format_raw($quotes[$cheapest_counter]['cost']),
          ];

          $default_shipping = $cheapest_counter;
        }

        if ( !isset($default_shipping) ) {
          $_SESSION['shipping'] = false;
        } else {
          $item_params['PAYMENTREQUEST_0_INSURANCEOPTIONOFFERED'] = 'false';
          $item_params['L_SHIPPINGOPTIONISDEFAULT' . $default_shipping] = 'true';

// Instant Update
          $item_params['CALLBACK'] = $GLOBALS['Linker']->build('ext/modules/payment/paypal/express.php', ['action' => 'callbackSet'], false);
          $item_params['CALLBACKTIMEOUT'] = '6';
          $item_params['CALLBACKVERSION'] = $paypal_express->api_version;

// set shipping for order total calculations; shipping in $item_params includes taxes
          $order->info['shipping_method'] = $item_params['L_SHIPPINGOPTIONNAME' . $default_shipping];
          $order->info['shipping_cost'] = $item_params['L_SHIPPINGOPTIONAMOUNT' . $default_shipping];

          $order->info['total'] = $order->info['subtotal'] + $order->info['shipping_cost'];

          if ( DISPLAY_PRICE_WITH_TAX == 'false' ) {
            $order->info['total'] += $order->info['tax'];
          }
        }

        $order_total_modules = new order_total();
        $order->totals = $order_total_modules->process();

// Remove shipping tax from total that was added again in ot_shipping
        if ( isset($default_shipping) ) {
          if (DISPLAY_PRICE_WITH_TAX == 'true') $order->info['shipping_cost'] = $order->info['shipping_cost'] / (1.0 + ($quotes[$default_shipping]['tax'] / 100));
          $module = substr($_SESSION['shipping']['id'], 0, strpos($_SESSION['shipping']['id'], '_'));
          $order->info['tax'] -= Tax::calculate($order->info['shipping_cost'], $quotes[$default_shipping]['tax']);
          $order->info['tax_groups'][Tax::get_description($GLOBALS[$module]->tax_class, $customer_data->get('country_id', $order->delivery), $customer_data->get('zone_id', $order->delivery))]
            -= Tax::calculate($order->info['shipping_cost'], $quotes[$default_shipping]['tax']);
          $order->info['total'] -= Tax::calculate($order->info['shipping_cost'], $quotes[$default_shipping]['tax']);
        }

        $items_total = $GLOBALS['currencies']->format_raw($order->info['subtotal']);

        foreach ($order->totals as $ot) {
          if ( !in_array($ot['code'], ['ot_subtotal', 'ot_shipping', 'ot_tax', 'ot_total']) ) {
            $item_params['L_PAYMENTREQUEST_0_NAME' . $line_item_no] = $ot['title'];
            $item_params['L_PAYMENTREQUEST_0_AMT' . $line_item_no] = $GLOBALS['currencies']->format_raw($ot['value']);

            $items_total += $GLOBALS['currencies']->format_raw($ot['value']);

            $line_item_no++;
          }
        }

        $params['PAYMENTREQUEST_0_AMT'] = $GLOBALS['currencies']->format_raw($order->info['total']);

// safely pad higher for dynamic shipping rates (eg, USPS express)
        $item_params['MAXAMT'] = $GLOBALS['currencies']->format_raw($params['PAYMENTREQUEST_0_AMT'] + $expensive_rate + 100, true, '', 1);
        $item_params['PAYMENTREQUEST_0_ITEMAMT'] = $items_total;
        $item_params['PAYMENTREQUEST_0_SHIPPINGAMT'] = $GLOBALS['currencies']->format_raw($order->info['shipping_cost']);

        $paypal_item_total = $item_params['PAYMENTREQUEST_0_ITEMAMT'] + $item_params['PAYMENTREQUEST_0_SHIPPINGAMT'];

        if ( DISPLAY_PRICE_WITH_TAX == 'false' ) {
          $item_params['PAYMENTREQUEST_0_TAXAMT'] = $GLOBALS['currencies']->format_raw($order->info['tax']);

          $paypal_item_total += $item_params['PAYMENTREQUEST_0_TAXAMT'];
        }
      } else {
        if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
          $params['PAYMENTREQUEST_0_AMT'] = $paypal_item_total;
        } else { // Payflow
          $params['AMT'] = $paypal_item_total;
        }
      }

      if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
        if ( $GLOBALS['currencies']->format_raw($paypal_item_total) == $params['PAYMENTREQUEST_0_AMT'] ) {
          $params = array_merge($params, $item_params);
        }
      } else { // Payflow
        if ( $GLOBALS['currencies']->format_raw($paypal_item_total) == $params['AMT'] ) {
          $params = array_merge($params, $item_params);
        }
      }

      $_SESSION['appPayPalEcSecret'] = Password::create_random(16, 'digits');

      if ( PAYPAL_APP_GATEWAY == '1' ) { // PayPal
        $params['PAYMENTREQUEST_0_CUSTOM'] = $_SESSION['appPayPalEcSecret'];

// Log In with PayPal token for seamless checkout
        if (isset($_SESSION['paypal_login_access_token'])) {
          $params['IDENTITYACCESSTOKEN'] = $_SESSION['paypal_login_access_token'];
        }

        $response_array = $paypal_express->_app->getApiResult('EC', 'SetExpressCheckout', $params);

        if ( in_array($response_array['ACK'], ['Success', 'SuccessWithWarning']) ) {
          if ( isset($_GET['format']) && ($_GET['format'] == 'json') ) {
            $result = [
              'token' => $response_array['TOKEN'],
            ];

            echo json_encode($result);
            exit;
          }

          Href::redirect($paypal_url . 'token=' . $response_array['TOKEN']);
        } else {
          Href::redirect($GLOBALS['Linker']->build('shopping_cart.php', ['error_message' => stripslashes($response_array['L_LONGMESSAGE0'])]));
        }
      } else { // Payflow
        $params['CUSTOM'] = $_SESSION['appPayPalEcSecret'];

        $params['_headers'] = [
          'X-VPS-REQUEST-ID: ' . md5($_SESSION['cartID'] . session_id() . $GLOBALS['currencies']->format_raw($paypal_item_total)),
          'X-VPS-CLIENT-TIMEOUT: 45',
          'X-VPS-VIT-INTEGRATION-PRODUCT: OSCOM',
          'X-VPS-VIT-INTEGRATION-VERSION: 2.3',
        ];

        $response_array = $paypal_express->_app->getApiResult('EC', 'PayflowSetExpressCheckout', $params);

        if ( $response_array['RESULT'] == '0' ) {
          Href::redirect($paypal_url . 'token=' . $response_array['TOKEN']);
        } else {
          Href::redirect($GLOBALS['Linker']->build('shopping_cart.php', ['error_message' => $response_array['PHOENIX_ERROR_MESSAGE']]));
        }
      }

      break;
  }

  Href::redirect($GLOBALS['Linker']->build('shopping_cart.php'));

  require DIR_FS_CATALOG . 'includes/application_bottom.php';
