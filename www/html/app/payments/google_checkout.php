<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/
use Tygh\Registry;
use Tygh\Session;
use Tygh\Shippings\Shippings;

if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
    define('SKIP_SESSION_VALIDATION', true);
    require './init_payment.php';
    require(Registry::get('config.dir.payments') . 'google_calculations.php');
    exit;
}

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {
    if (!empty($_SESSION['order_id'])) {
        fn_order_placement_routines('route', $_SESSION['order_id']);
    } else {
        fn_set_notification('E', __('error'), __('order_was_not_placed'));
        fn_order_placement_routines('checkout_redirect');
    }

} elseif (!empty($_payment_id) && !fn_cart_is_empty($cart) && $processor_data['processor_params']['policy_agreement'] == 'Y') {
    $return_url = htmlspecialchars(fn_url("payment_notification.notify?payment=google_checkout&" . Session::getName() . '=' . Session::getId(), 'C'));
    $edit_cart_url = fn_url("checkout.cart", 'C');

    $method = ($processor_data["processor_params"]["test"] == 'N') ? 'https' : 'current';
    $calculation_url = fn_payment_url($method, 'google_checkout.php');

    $_currency = $processor_data['processor_params']['currency'];
    $base_domain = 'https://' . (($processor_data['processor_params']['test'] == 'N') ? 'checkout.google.com' : 'sandbox.google.com/checkout');
    $base_url = $base_domain . '/cws/v2/Merchant/' . $processor_data['processor_params']['merchant_id'];
    $checkout_url = $base_url . '/checkout';
    $request_url = $base_url . '/request';

    // Form XML array with cart items
    $_items = '';

    $google_products = $cart_products;

    fn_set_hook('google_products', $google_products, $cart);

    if (!empty($google_products) && is_array($google_products)) {
        foreach ($google_products as $k => $v) {
            $item_options = '';
            if (!empty($v['product_options'])) {
                $_options = fn_get_selected_product_options_info($cart['products'][$k]['product_options']);
                foreach ($_options as $opt) {
                    $item_options .= $opt['option_name'] . ': ' . $opt['variant_name'] . '; ';
                }
                $item_options = ' [' . trim($item_options, '; ') . ']';
            }

    $_items .= '<item>' .
                    '<merchant-item-id>' . $v['product_id'] . '</merchant-item-id>' .
                    '<item-name>' . strip_tags($v['product']) . $item_options . '</item-name>'.
                    '<item-description>' . substr(strip_tags($v['short_description']), 0, 299) . '</item-description>' .
                    "<unit-price currency='" . $_currency . "'>" . fn_format_price($v['price']) . '</unit-price>' .
                    '<quantity>' . $v['amount'] . '</quantity>' .
                '</item>';
        }
    }

    fn_get_google_add_items($_items, $cart, $_currency);

    // Prepare taxes
    $_taxes_list = fn_get_taxes();
    $taxes = '';
    foreach ($_taxes_list as $v) {
        if ($v['status'] == 'A' && $v['price_includes_tax'] != 'Y') {
            $_tax_rate = !empty($processor_data['processor_params']['default_taxes'][$v['tax_id']]) ? fn_format_price($processor_data['processor_params']['default_taxes'][$v['tax_id']])/100 : 0;
            $taxes .= "
        <default-tax-rule>
        <shipping-taxed>false</shipping-taxed>
        <rate>$_tax_rate</rate>
        <tax-area>
            <us-country-area country-area=\"FULL_50_STATES\" />
        </tax-area>
        </default-tax-rule>";
        }
    }

    if (!empty($taxes)) {
        $taxes = "<tax-tables merchant-calculated=\"true\">
                    <default-tax-table>
                    <tax-rules>
                        $taxes
                    </tax-rules>
                    </default-tax-table>
                </tax-tables>";
    }

    // ******************************** Prepare shippings *************************

    $_shipping_methods = fn_prepare_google_shippings();

    // non-real shipping is for google to display that no shipping available (otherwise there will be no such massage and customer will place order)
    if (empty($_shipping_methods) && $cart['shipping_required'] == true) {
        $_shipping_methods[0]['shipping'] = "--";
        $_shipping_methods[0]['shipping_id'] = 'id';
    }

    $shippings = "<shipping-methods>";
    $private_ship_data = '<shippings>';
    foreach ($_shipping_methods as $_shipping) {
        $shipping_name = htmlspecialchars($_shipping['shipping'] . (!empty($_shipping['delivery_time']) ? ' ('.$_shipping['delivery_time'].')' : ''));
        // Get shipping_rate
        $_ship_rate = fn_google_default_shipping_rate($_shipping, $processor_data);
        $private_ship_data .= "<method name=\"$shipping_name\" id=\"$_shipping[shipping_id]\" />\n";
        $shippings .= "
        <merchant-calculated-shipping name=\"$shipping_name\">
          <price currency=\"$_currency\">$_ship_rate</price>
             <shipping-restrictions>
               <allowed-areas>
                 <world-area />
               </allowed-areas>
             </shipping-restrictions>
             <address-filters>
               <allowed-areas>
                 <world-area />
               </allowed-areas>
             </address-filters>
        </merchant-calculated-shipping>";
    }
    $private_ship_data .= '</shippings>';
    $shippings .= "</shipping-methods>";

    // ******************************** /Prepare shippings *************************

     // Form a discount part of a form
    if (!empty($cart['subtotal_discount']) && floatval($cart['subtotal_discount'])) {
    $_items .= "
      <item>
        <item-name>" . __('order_discount') . "</item-name>
        <item-description>" . __('order_discount') . "</item-description>
        <unit-price currency='" . $_currency . "'>" . -$cart['subtotal_discount'] . "</unit-price>
        <quantity>1</quantity>
      </item>";
    }

    // Form a surcharge part of the payment
    if (!empty($_payment_id)) {
        $_data = db_get_row("SELECT a_surcharge, p_surcharge FROM ?:payments WHERE payment_id = ?i", $_payment_id);
        $cart['payment_surcharge'] = 0;
        if (floatval($_data['a_surcharge'])) {
            $cart['payment_surcharge'] += $_data['a_surcharge'];
        }
        if (floatval($_data['p_surcharge'])) {
            $cart['payment_surcharge'] += fn_format_price($cart['total'] * $_data['p_surcharge'] / 100);
        }
        if (!empty($cart['payment_surcharge'])) {
            $_items .= "
              <item>
                <item-name>" . __('surcharge') . "</item-name>
                <item-description>" . __('payment_surcharge') . "</item-description>
                <unit-price currency='" . $_currency . "'>" . $cart['payment_surcharge'] . "</unit-price>
                <quantity>1</quantity>
                <tax-table-selector>no_tax</tax-table-selector>
              </item>";
        }
    }

    // The cart in XML format
    $xml_cart = "<?xml version='1.0' encoding='UTF-8'?>
    <checkout-shopping-cart xmlns='http://checkout.google.com/schema/2'>
      <shopping-cart>
        <merchant-private-data>
            <additional_data>
                <session_id>" . Session::getId() . "</session_id>
                <currency_code>" . $_currency . "</currency_code>
                <payment_id>" . $_payment_id . "</payment_id>
                " . $private_ship_data ."
            </additional_data>
        </merchant-private-data>
        <items>" .
            $_items .
        "</items>
      </shopping-cart>
      <checkout-flow-support>
        <merchant-checkout-flow-support>
          <platform-id>971865505315434</platform-id>
          <request-buyer-phone-number>true</request-buyer-phone-number>
          <edit-cart-url>" . $edit_cart_url . "</edit-cart-url>
          <merchant-calculations>
            <merchant-calculations-url>" . $calculation_url . "</merchant-calculations-url>
            " . fn_google_coupons_calculation($cart) . "
          </merchant-calculations>
          <continue-shopping-url>" . $return_url . "</continue-shopping-url>
        " . $shippings
         . $taxes. "
        </merchant-checkout-flow-support>
      </checkout-flow-support>
    </checkout-shopping-cart>";

    $signature = fn_calc_hmac_sha1($xml_cart, $processor_data['processor_params']['merchant_key']);
    $b64_cart = base64_encode($xml_cart);
    $b64_signature = base64_encode($signature);

    if (empty($_payment_id)) {
        $_payment_id = '0';
    }

    $checkout_buttons[$_payment_id] = '
    <html>
    <body>
    <form method="post" action="' . $checkout_url . '" name="BB_BuyButtonForm">
        <input type="hidden" name="cart" value="' . $b64_cart . '" />
        <input type="hidden" name="signature" value="' . $b64_signature . '" />
        <input alt="" src="' . $base_domain . '/buttons/checkout.gif?merchant_id=' . $processor_data['processor_params']['merchant_id'] . '&amp;w=160&amp;h=43&amp;style=' . $processor_data['processor_params']['button_type'] . '&amp;variant=text&amp;loc=en_US" type="image"/>
        </form>
     </body>
    </html>';

}

//
// The CalcHmacSha1 function computes the HMAC-SHA1 signature that you need
// to send a Checkout API request. The signature is used to verify the
// integrity of the data in your API request.
//
// @param    $data    message data
// @return   $hmac    value of the calculated HMAC-SHA1
function fn_calc_hmac_sha1($data, $key)
{
    $blocksize = 64;
    $hashfunc = 'sha1';

    if (strlen($key) > $blocksize) {
        $key = pack('H*', $hashfunc($key));
    }

    $key = str_pad($key, $blocksize, chr(0x00));
    $ipad = str_repeat(chr(0x36), $blocksize);
    $opad = str_repeat(chr(0x5c), $blocksize);
    $hmac = pack(
                    'H*', $hashfunc(
                            ($key^$opad).pack(
                                    'H*', $hashfunc(
                                            ($key^$ipad).$data
                                    )
                            )
                    )
                );

    return $hmac;
}

function fn_prepare_google_shippings()
{
    $_shipping_methods = array();

    $location = fn_get_customer_location($_SESSION['auth'], $_SESSION['cart']);
    $product_groups = Shippings::groupProductsList($_SESSION['cart']['products'], $location);

    foreach ($product_groups as $key_group => $group) {
        $shipping_methods = Shippings::getShippingsList($group);
        foreach ($shipping_methods as $shipping) {
            if (empty($_shipping_methods[$shipping['shipping_id']])) {
                $_shipping_methods[$shipping['shipping_id']] = $shipping;
                $_shipping_methods[$shipping['shipping_id']]['rate'] = 0;
            }
            if (isset($_SESSION['cart']['shipping'][$shipping['shipping_id']]['rate'])) {
                $_shipping_methods[$shipping['shipping_id']]['rate'] += $_SESSION['cart']['shipping'][$shipping['shipping_id']]['rate'];
            }
        }
    }

    fn_set_hook('prepare_google_shippings', $_shipping_methods);

    return $_shipping_methods;
}

// Define strings whether coupons should be calculated or not
function fn_google_coupons_calculation($cart)
{
    $string = '';
    $string .= '<accept-merchant-coupons>' . ((!empty($cart['no_coupons'])) ? 'false' : 'true') . '</accept-merchant-coupons>';

    fn_set_hook('google_coupons_calculation', $string);

    return $string;
}

// Check some additional items that should be passed to the Google Checkout
function fn_get_google_add_items(&$_items, $cart, $_currency)
{
    fn_set_hook('get_google_add_items', $_items, $cart, $_currency);

    return true;
}

// Get default shipping rates
function fn_google_default_shipping_rate($shipping, $processor_data)
{
    $rate = 0;

    foreach ($processor_data['processor_params']['default_shippings'] as $s_id => $s_rate) {
        if (strpos($shipping['shipping_id'], (string) $s_id) !== false) {
            $rate += $s_rate;
        }
    }

    return empty($rate) ? 0 : $rate;
}
