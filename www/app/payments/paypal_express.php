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

use Tygh\Http;
use Tygh\Registry;

if (defined('PAYMENT_NOTIFICATION')) {

    if ($mode == 'cancel') {

        $order_info = fn_get_order_info($_REQUEST['order_id']);

        if ($order_info['status'] == 'O' || $order_info['status'] == 'I') {
            $pp_response['order_status'] = 'I';
            $pp_response["reason_text"] = __('text_transaction_cancelled');
            fn_finish_payment($order_info['order_id'], $pp_response);
        }

        fn_order_placement_routines('route', $_REQUEST['order_id'], false);

    } else {
        $order_id = (!empty($_REQUEST['order_id'])) ? $_REQUEST['order_id'] : 0;
        $token = (!empty($_REQUEST['token'])) ? $_REQUEST['token'] : 0;

        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        $processor_data = fn_get_payment_method_data($payment_id);
        $order_info = fn_get_order_info($order_id);

        fn_paypal_complete_checkout($token, $processor_data, $order_info);
    }
}

$mode = (!empty($mode)) ? $mode : (!empty($_REQUEST['mode']) ? $_REQUEST['mode'] : '');

if (!empty($_payment_id) && (!empty($_SESSION['cart']['products']) || !empty($_SESSION['cart']['gift_certificates'])) && Registry::get('runtime.mode') == 'cart') {

    $checkout_buttons[$_payment_id] = '
        <html>
        <body>
        <br/>
        <form name="pp_express" action="'. fn_payment_url('current', 'paypal_express.php') . '" method="post">
            <input name="payment_id" value="'.$_payment_id.'" type="hidden" />
            <input src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" type="image" />
            <input name="mode" value="express" type="hidden" />
        </form>
        </body>
        </html>';

} elseif ($mode == 'express_return') {

    if (!defined('BOOTSTRAP')) {
        require './init_payment.php';
    }

    $token = $_REQUEST['token'];
    $payment_id = $_REQUEST['payment_id'];

    $processor_data = fn_get_payment_method_data($payment_id);
    $paypal_checkout_details = fn_paypal_get_express_checkout_details($processor_data, $token);

    if (fn_paypal_ack_success($paypal_checkout_details)) {
        fn_paypal_user_login($paypal_checkout_details);

        $paypal_express_details = array(
            'token' => $token,
            'payment_id' => $payment_id
        );
        $_SESSION['pp_express_details'] = $paypal_express_details;
        $_SESSION['cart']['payment_id'] = $payment_id;
    } else {
        fn_paypal_get_error($paypal_checkout_details);
    }

    fn_order_placement_routines('checkout_redirect');

} elseif ($mode == 'place_order' && !empty($_SESSION['pp_express_details'])) {

    $token = $_SESSION['pp_express_details']['token'];
    fn_paypal_complete_checkout($token, $processor_data, $order_info);

} elseif ($mode == 'place_order' || $mode == 'express' || $mode == 'repay') {

    if (!defined('BOOTSTRAP')) {
        require './init_payment.php';
        $_SESSION['cart'] = empty($_SESSION['cart']) ? array() : $_SESSION['cart'];
    }

    $payment_id = (empty($_REQUEST['payment_id']) ? $_SESSION['cart']['payment_id'] : $_REQUEST['payment_id']);

    if ($mode == 'express') {
        $result = fn_paypal_set_express_checkout($payment_id, 0, array(), $_SESSION['cart']);
        $useraction = 'continue';
    } else {
        $result = fn_paypal_set_express_checkout($payment_id, $order_id, $order_info);
        $useraction = "commit";
    }

    if (fn_paypal_ack_success($result) && !empty($result['TOKEN'])) {

        $processor_data = fn_get_payment_method_data($payment_id);

        if ($processor_data['processor_params']['mode'] == 'live') {
            $host = 'https://www.paypal.com';
        } else {
            $host = 'https://www.sandbox.paypal.com';
        }

        $post_data = array(
            'cmd' => '_express-checkout',
            'token' => $result['TOKEN'],
            'useraction' => $useraction,
        );

        $submit_url = "$host/webscr";

        fn_create_payment_form($submit_url, $post_data, 'Paypal Express');

    } else {
        fn_paypal_get_error($result);

        if ($mode == 'express') {
            fn_order_placement_routines('checkout.cart');
        } else {
            fn_order_placement_routines('checkout_redirect');
        }
    }
}

function fn_paypal_complete_checkout($token, $processor_data, $order_info)
{
    $pp_response['order_status'] = 'F';
    $reason_text = '';

    $paypal_checkout_details = fn_paypal_get_express_checkout_details($processor_data, $token);

    if (fn_paypal_ack_success($paypal_checkout_details)) {

        $result = fn_paypal_do_express_checkout($processor_data, $paypal_checkout_details, $order_info);
        if (fn_paypal_ack_success($result)) {

            $status = $result['PAYMENTINFO_0_PAYMENTSTATUS'];
            $pp_response['transaction_id'] = $result['PAYMENTINFO_0_TRANSACTIONID'];

            if ($status == 'Completed' || $status == 'Processed') {
                $pp_response['order_status'] = 'P';
                $reason_text = 'Accepted ';

            } elseif ($status == 'Pending') {
                $pp_response['order_status'] = 'O';
                $reason_text = 'Pending ';

            } else {
                $reason_text = 'Declined ';
            }

            $reason_text = fn_paypal_process_add_fields($result, $reason_text);

            if (!empty($result['L_ERRORCODE0'])) {
                $reason_text .= ', ' . fn_paypal_get_error($result);
            }
        } else {
            $reason_text = fn_paypal_get_error($result);
        }
    } else {
        $reason_text = fn_paypal_get_error($paypal_checkout_details);
    }

    $pp_response['reason_text'] = $reason_text;

    if (fn_check_payment_script('paypal_express.php', $order_info['order_id'])) {
        unset($_SESSION['pp_express_details']);

        fn_finish_payment($order_info['order_id'], $pp_response);
        fn_order_placement_routines('route', $order_info['order_id'], false);
    }
}

function fn_paypal_ack_success($paypal_checkout_details)
{
    return !empty($paypal_checkout_details['ACK']) && ($paypal_checkout_details['ACK'] == 'Success' || $paypal_checkout_details['ACK'] == 'SuccessWithWarning');
}

function fn_paypal_user_login($checkout_details)
{
    $s_firstname = $s_lastname = '';
    if (!empty($checkout_details['SHIPTONAME'])) {
        $name = explode(' ', $checkout_details['SHIPTONAME']);
        $s_firstname = $name[0];
        unset($name[0]);
        $s_lastname = (!empty($name[1]))? implode(' ', $name) : '';
    }

    $s_state = $checkout_details['SHIPTOSTATE'];
    $s_state_codes = db_get_hash_array("SELECT ?:states.code, lang_code FROM ?:states LEFT JOIN ?:state_descriptions ON ?:state_descriptions.state_id = ?:states.state_id WHERE ?:states.country_code = ?s AND ?:state_descriptions.state = ?s", 'lang_code',  $checkout_details['SHIPTOCOUNTRYNAME'], $s_state);

    if (!empty($s_state_codes[CART_LANGUAGE])) {
        $s_state = $s_state_codes[CART_LANGUAGE]['code'];
    } elseif (!empty($s_state_codes)) {
        $s_state = array_pop($s_state_codes);
        $s_state = $s_state['code'];
    }

    $address = array (
        's_firstname' => $s_firstname,
        's_lastname' => $s_lastname,
        's_address' => $checkout_details['SHIPTOSTREET'],
        's_address_2' => !empty($checkout_details['SHIPTOSTREET2']) ? $checkout_details['SHIPTOSTREET2'] : '',
        's_city' => $checkout_details['SHIPTOCITY'],
        //'s_county' => $checkout_details['SHIPTOCOUNTRYNAME'],
        's_state' => $s_state,
        's_country' => $checkout_details['SHIPTOCOUNTRYNAME'],
        's_zipcode' => $checkout_details['SHIPTOZIP']
    );

    $_SESSION['auth'] = empty($_SESSION['auth']) ? array() : $_SESSION['auth'];
    $auth = & $_SESSION['auth'];

    // Update profile info if customer is registered user
    if (!empty($auth['user_id']) && $auth['area'] == 'C') {
        foreach ($address as $k => $v) {
            $_SESSION['cart']['user_data'][$k] = $v;
        }

        $profile_id = !empty($_SESSION['cart']['profile_id']) ? $_SESSION['cart']['profile_id'] : db_get_field("SELECT profile_id FROM ?:user_profiles WHERE user_id = ?i AND profile_type='P'", $auth['user_id']);
        db_query('UPDATE ?:user_profiles SET ?u WHERE profile_id = ?i', $_SESSION['cart']['user_data'], $profile_id);

        // Or jyst update info in the cart
    } else {
        // fill customer info
        $_SESSION['cart']['user_data'] = array(
            'firstname' => $checkout_details['FIRSTNAME'],
            'lastname' => $checkout_details['LASTNAME'],
            'email' => $checkout_details['EMAIL'],
            'company' => '',
            'phone' => '',
            'fax' => '',
        );

        foreach ($address as $k => $v) {
            $_SESSION['cart']['user_data'][$k] = $v;
            $_SESSION['cart']['user_data']['b_' . substr($k, 2)] = $v;
        }
    }

    return true;
}

function fn_paypal_build_request($processor_data, &$request, &$post_url, &$cert_file)
{
    $request = array_merge($request, array(
        'USER' => $processor_data['processor_params']['username'],
        'PWD' => $processor_data['processor_params']['password'],
        'VERSION' => 106,
    ));

    if (!empty($processor_data['processor_params']['authentication_method']) && $processor_data['processor_params']['authentication_method'] == 'signature') {
        $request['SIGNATURE'] = $processor_data['processor_params']['signature'];
        $url_prefix = '-3t';
        $cert_file = '';
    } else {
        $url_prefix = '';
        $cert_file = Registry::get('config.dir.certificates') . $processor_data['processor_params']['certificate_filename'];
    }

    if ($processor_data['processor_params']['mode'] == 'live') {
        $post_url = "https://api$url_prefix.paypal.com:443/nvp";
    } else {
        $post_url = "https://api$url_prefix.sandbox.paypal.com:443/nvp";
    }

    return true;
}

function fn_paypal_set_express_checkout($payment_id, $order_id = 0, $order_info = array(), $cart = array())
{
    $processor_data = fn_get_payment_method_data($payment_id);

    if (!empty($order_id)) {
        $return_url = fn_url("payment_notification.notify?payment=paypal_express&order_id=$order_id", 'C', 'current');
        $cancel_url = fn_url("payment_notification.cancel?payment=paypal_express&order_id=$order_id", 'C', 'current');
    } else {
        $return_url = fn_payment_url('current', "paypal_express.php?mode=express_return&payment_id=$payment_id");
        $cancel_url = fn_url("checkout.cart", 'C', 'current');
    }

    $request = array(
        'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
        'PAYMENTREQUEST_0_CURRENCYCODE' => $processor_data['processor_params']['currency'],
        'LOCALECODE' => CART_LANGUAGE,
        'RETURNURL' => $return_url,
        'CANCELURL' => $cancel_url,
        'METHOD' => 'SetExpressCheckout'
    );

    fn_paypal_build_request($processor_data, $request, $post_url, $cert_file);

    $order_details = (!empty($order_info)) ? fn_paypal_build_details($order_info, $processor_data, false) : fn_paypal_build_details($cart, $processor_data);
    $request = array_merge($request, $order_details);

    return fn_paypal_request($request, $post_url, $cert_file);
}

function fn_paypal_get_express_checkout_details($processor_data, $token)
{
    $request = array(
        'TOKEN' => $token,
        'METHOD' => 'GetExpressCheckoutDetails'
    );

    fn_paypal_build_request($processor_data, $request, $post_url, $cert_file);

    return fn_paypal_request($request, $post_url, $cert_file);
}

function fn_paypal_do_express_checkout($processor_data, $paypal_checkout_details, $order_info)
{
    $request = array(
        'PAYERID' => $paypal_checkout_details['PAYERID'],
        'TOKEN' => $paypal_checkout_details['TOKEN'],
        'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
        'PAYMENTREQUEST_0_CURRENCYCODE' => $processor_data['processor_params']['currency'],
        'PAYMENTREQUEST_0_AMT' => $order_info['total'],
        'METHOD' => 'DoExpressCheckoutPayment'
    );

    fn_paypal_build_request($processor_data, $request, $post_url, $cert_file);

    return fn_paypal_request($request, $post_url, $cert_file);
}

function fn_paypal_request($request, $post_url, $cert_file)
{
    $extra = array(
        'headers' => array(
            'Connection: close'
        ),
        'ssl_cert' => $cert_file
    );

    $response = Http::post($post_url, $request, $extra);

    if (!empty($response)) {
        parse_str($response, $result);

    } else {
        $result['ERROR'] = Http::getError();
    }

    return $result;
}

function fn_paypal_build_details($data, $processor_data, $express = true)
{
    $details = array();
    $shipping_data = array();

    if (!empty($processor_data['processor_params']['send_adress']) && $processor_data['processor_params']['send_adress'] == 'Y') {
        if ($express) {
            $shipping_data = fn_paypal_get_shipping_data($data['user_data']);
        } else {
            $shipping_data = fn_paypal_get_shipping_data($data);
        }
    }

    $order_data = fn_paypal_get_order_data($data);

    return array_merge($details, $shipping_data, $order_data);
}

function fn_paypal_get_shipping_data($data)
{
    $shipping_data = array();

    if (!empty($data)) {
        $shipping_data['ADDROVERRIDE'] = 1;
        $shipping_data['PAYMENTREQUEST_0_SHIPTONAME'] = $data['s_firstname'] . ' ' . $data['s_lastname'];
        $shipping_data['PAYMENTREQUEST_0_SHIPTOSTREET'] = $data['s_address'];
        if (!empty($data['s_address_2'])) {
            $shipping_data['PAYMENTREQUEST_0_SHIPTOSTREET2'] = $data['s_address_2'];
        }
        $shipping_data['PAYMENTREQUEST_0_SHIPTOCITY'] = $data['s_city'];
        $shipping_data['PAYMENTREQUEST_0_SHIPTOSTATE'] = $data['s_state'];
        $shipping_data['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $data['s_country'];
        $shipping_data['PAYMENTREQUEST_0_SHIPTOZIP'] = $data['s_zipcode'];
    }

    return $shipping_data;
}

function fn_paypal_get_order_data($data)
{
    $order_data = array();
    $product_index = 0;

    foreach ($data['products'] as $product) {
        $order_data['L_PAYMENTREQUEST_0_NAME' . $product_index] = $product['product'];
        $order_data['L_PAYMENTREQUEST_0_NUMBER' . $product_index] = $product['product_code'];
        $order_data['L_PAYMENTREQUEST_0_DESC' . $product_index] = fn_paypal_get_product_option($product);
        $order_data['L_PAYMENTREQUEST_0_QTY' . $product_index] = $product['amount'];
        $order_data['L_PAYMENTREQUEST_0_AMT' . $product_index] = $product['price'];

        $product_index++;
    }

    $order_data['PAYMENTREQUEST_0_ITEMAMT'] = empty($data['subtotal_discount']) ? $data['subtotal'] : $data['subtotal'] - $data['subtotal_discount'];
    $order_data['PAYMENTREQUEST_0_TAXAMT'] = fn_paypal_sum_taxes($data);
    $order_data['PAYMENTREQUEST_0_SHIPPINGAMT'] = $data['shipping_cost'];
    $order_data['PAYMENTREQUEST_0_AMT'] = $data['total'];

    fn_paypal_apply_discount($data, $order_data, $product_index);
    fn_set_hook('paypal_express_get_order_data', $data, $order_data, $product_index);

    return $order_data;
}

function fn_paypal_sum_taxes($order_info)
{
    $sum_taxes = 0;
    if (!empty($order_info['taxes'])) {
        foreach ($order_info['taxes'] as $tax) {
            if ($tax['price_includes_tax'] != 'Y') {
                $sum_taxes += $tax['tax_subtotal'];
            }
        }
    }

    return $sum_taxes;
}

function fn_paypal_apply_discount($data, &$order_data, $product_index)
{
    if (!empty($data['subtotal_discount'])) {
        $order_data['L_PAYMENTREQUEST_0_NAME' . $product_index] = __('discount');
        $order_data['L_PAYMENTREQUEST_0_QTY' . $product_index] = 1;
        $order_data['L_PAYMENTREQUEST_0_AMT' . $product_index] = -$data['subtotal_discount'];
    }
}

function fn_paypal_get_product_option($product)
{
    $options = array();
    if (!empty($product['extra']['product_options'])) {
        foreach ($product['extra']['product_options'] as $option_id => $variant_id) {
            $option = fn_get_product_option_data($option_id, $product['product_id']);
            if ($option['option_type'] == 'F') {
                if (!empty($product['extra']['custom_files'][$option_id])) {
                    $files = array();
                    foreach ($product['extra']['custom_files'][$option_id] as $file) {
                        $files[] = $file['name'];
                    }
                    $options[] = $option['option_name'] . ': ' . implode(',', $files);
                }
            } elseif ($option['option_type'] == 'C') {
                if (!empty($option['variants'][$variant_id])) {
                    $options[] = $option['option_name'];
                }

            } elseif (empty($option['variants'])) {
                if (!empty($variant_id)) {
                    $options[] = $option['option_name'] . ': ' . $variant_id;
                }
            } else {
                $options[] = $option['option_name'] . ': ' . $option['variants'][$variant_id]['variant_name'];
            }

        }
    }

    return implode(", ", $options);
}

function fn_paypal_process_add_fields($result, $reason_text)
{
    $fields = array();
    //'ExchangeRate', 'GrossAmount','SettleAmount'
    $result_fields = array(
        'FEEAMT' => 'FeeAmount',
        'TAXAMT' => 'TaxAmount',
        'TRANSACTIONTYPE' => 'TransactionType',
        'PAYMENTTYPE' => 'PaymentType'
    );

    foreach ($result_fields as $field_id => $field_name) {
        $field = 'PAYMENTINFO_0_' . $field_id;
        if (isset($result[$field]) && strlen($result[$field]) > 0) {
            $fields[] = $field_name . ': ' . $result[$field];
        }
    }

    if (!empty($fields)) {
        $reason_text .= '(' . implode(', ', $fields) . ')';
    }

    return $reason_text;
}

function fn_paypal_get_error($result)
{
    $error_text = '';

    if (!empty($result['L_ERRORCODE0'])) {
        $error_text = $result['L_SHORTMESSAGE0'] . ': ' . $result['L_LONGMESSAGE0'];
        fn_set_notification('E', __('Error') . ' ' . $result['L_ERRORCODE0'], $error_text);

        $error_text = '(' . $result['L_ERRORCODE0'] . ') ' . $error_text;
    }

    return $error_text;
}
