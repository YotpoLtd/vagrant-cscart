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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

    $processor_response = array(
        "canceled" => "Customer canceled from SIPS Payment. No payment has been done.",
        "error" => "SIPS application error.",
        "inprocess" => "Transaction in process, waiting for creditcard approval result.",
        "approved" => "Creditcard authorization approved.",
        "declined" => "Creditcard authorization declined.",
        "outofservice" => "Outofservice.",
    );

    $processor_error = array (
        "002" => "Host Approve",
            "003" => "Host Reject",
            "006" => "Error",
            "007" => "SIPS is down",
            "008" => "SIPS is down",
    );

    if ($mode == 'process') {
        fn_order_placement_routines('route', $_REQUEST['order_id']);

    } elseif ($mode == 'result' || empty($mode)) {

        $order_id = (strpos($_REQUEST['ref_no'], '_')) ? substr($_REQUEST['ref_no'], 0, strpos($_REQUEST['ref_no'], '_')) : $_REQUEST['ref_no'];

        if (!empty($_REQUEST['payment_status']) && $_REQUEST['payment_status'] == '002') {
            $pp_response["order_status"] = 'P';
            $pp_response["reason_text"] = "Approval Code: " . $_REQUEST['appr_code'];

        } else {
            $pp_response["order_status"] = 'F';
            $pp_response["reason_text"] = "Response code: ";
            if (!empty($processor_error[$_REQUEST['payment_status']])) {
                $pp_response["reason_text"] .= $processor_error[$_REQUEST['payment_status']];
            } else {
                $pp_response["reason_text"] .= $_REQUEST['payment_status'];
            }
        }

        $pp_response['transaction_id'] = $_REQUEST['trans_no'];

        if (fn_check_payment_script('scb.php', $_REQUEST['order_id'])) {
            fn_finish_payment($_REQUEST['order_id'], $pp_response);
        }

        exit;
    }

} else {

$customer_url = fn_url("payment_notification.process?payment=scb&order_id=$order_id", AREA, 'current');
$_order_id = ($order_info['repaid']) ? ($order_id . '_' . $order_info['repaid']) : $order_id;
$today = date('Ymdhis');

echo <<<EOT
<form method="post" action="https://sips.scb.co.th/cc/webcredit/web_credit_payment.phtml" name="process">
    <input type="hidden" name="mid" value="{$processor_data['processor_params']['merchant_id']}">
    <input type="hidden" name="terminal" value="{$processor_data['processor_params']['terminal_id']}">
    <input type="hidden" name="version" value="2_5_1">
    <input type="hidden" name="command" value="CRAUTH">
    <input type="hidden" name="ref_no" value="{$_order_id}">
    <input type="hidden" name="ref_date" value="{$today}">
    <input type="hidden" name="service_id" value="00">
    <input type="hidden" name="cust_id" value="{$order_info['user_id']}">
    <input type="hidden" name="cur_abbr" value="{$processor_data['processor_params']['currency']}">
    <input type="hidden" name="amount" value="{$order_info['total']}">
    <input type="hidden" name="cust_lname" value="{$order_info['lastname']}">
    <input type="hidden" name="cust_fname" value="{$order_info['firstname']}">
    <input type="hidden" name="cust_email" value="{$order_info['email']}">
    <input type="hidden" name="cust_country" value="{$order_info['b_country']}">
    <input type="hidden" name="cust_address1" value="{$order_info['b_address']}">
    <input type="hidden" name="description" value="Shopping cart">
    <input type="hidden" name="cust_address2" value="{$order_info['b_address_2']}">
    <input type="hidden" name="cust_city" value="{$order_info['b_city']}">
    <input type="hidden" name="cust_province" value="{$order_info['b_state']}">
    <input type="hidden" name="cust_zip" value="{$order_info['b_zipcode']}">
    <input type="hidden" name="backURL" value="{$customer_url}">

EOT;

$msg = __('text_cc_processor_connection', array(
    '[processor]' => 'SCB server'
));
echo <<<EOT
    </form>
   <p><div align=center>{$msg}</div></p>
    <script type="text/javascript">
    window.onload = function(){
        document.process.submit();
    };
    </script>
 </body>
</html>
EOT;
exit;
}
