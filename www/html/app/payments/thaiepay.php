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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

    if (empty($_REQUEST['refno'])) {
        if (!empty($_SESSION['thaiepay_refno'])) {
            $_REQUEST['refno'] = $_SESSION['thaiepay_refno'];
            unset($_SESSION['thaiepay_refno']);
        } else {
            if ($mode == 'finish') {
                fn_order_placement_routines('checkout_redirect');
            }
            exit;
        }
    }
    $order_id = intval($_REQUEST['refno']);

    if (fn_check_payment_script('thaiepay.php', $order_id, $processor_data)) {

        if ($mode == 'notify') {

            $errors = array();
            $errors_desc = array (
                'additional_parameter' => __('additional_parameter_not_correct'),
                'total' => __('order_total_not_correct'),
            );

            if (isset($_REQUEST['total'])) {
                $order_info = fn_get_order_info($order_id);
                if (fn_format_price($order_info['total']) != fn_format_price($_REQUEST['total'])) {
                    $errors['total'] = true;
                }
            }

            $param_name = !empty($processor_data['processor_params']['add_param_name']) ? $processor_data['processor_params']['add_param_name'] : '';
            $param_value = !empty($processor_data['processor_params']['add_param_value']) ? $processor_data['processor_params']['add_param_value'] : '';
            $sec_param = (!empty($param_name) && !empty($_REQUEST[$param_name])) ? $_REQUEST[$param_name] : '';

            if (empty($param_value) || empty($sec_param) || $sec_param != $param_value) {
                $errors['additional_parameter'] = true;
            }

            $pp_response = array();
            $pp_response['reason_text'] = __('order_id') . '-' . $order_id;
            $pp_response['transaction_id'] = '';

            if ($errors) {
                $pp_response['order_status'] = 'F';
                foreach ($errors as $error => $v) {
                    $pp_response['reason_text'] = $pp_response['reason_text'] . "\n" . $errors_desc[$error];
                }
            } else {
                $pp_response['order_status'] = 'P';
            }

            fn_finish_payment($order_id, $pp_response);
            exit;

        } elseif ($mode == 'finish') {
            $order_info = fn_get_order_info($order_id);
            if ($order_info['status'] == 'O') {
                $pp_response = array();
                $pp_response['order_status'] = 'F';
                $pp_response['reason_text'] = __('merchant_response_was_not_received');
                $pp_response['transaction_id'] = '';
                fn_finish_payment($order_id, $pp_response);
            }
            fn_order_placement_routines('route', $order_id, false);
        }
    }

} else {
    $current_location = Registry::get('config.current_location');
    $lang_code = (CART_LANGUAGE == 'th') ? 'TH' : 'EN';
    $sess = '&' . Session::getName() . '=' . Session::getId();
    $_SESSION['thaiepay_refno'] = $order_id;
    $return_url = fn_url("payment_notification.finish?payment=thaiepay&refno={$order_id}{$sess}", AREA, 'current');
echo <<<EOT
<form method="post" action="https://www.thaiepay.com/epaylink/payment.aspx" name="process">
    <input type="hidden" name="refno" value="{$order_id}">
    <input type="hidden" name="merchantid" value="{$processor_data['processor_params']['merchantid']}">
    <input type="hidden" name="customeremail" value="{$order_info['email']}">
    <input type="hidden" name="productdetail" value="{$processor_data['processor_params']['details']}">
    <input type="hidden" name="total" value="{$order_info['total']}">
    <input type="hidden" name="cc" value="{$processor_data['processor_params']['currency']}">
    <input type="hidden" name="lang" value="{$lang_code}">
    <input type="hidden" name="returnurl" value="{$return_url}">
EOT;

$msg = __('text_cc_processor_connection', array(
    '[processor]' => 'thaiepay.com server'
));
echo <<<EOT
    </form>
    <div align=center>{$msg}</div>
    <script type="text/javascript">
    window.onload = function(){
        document.process.submit();
    };
    </script>
 </body>
</html>
EOT;

}
exit;
