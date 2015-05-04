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
    $pp_response['order_status'] = ($_REQUEST['status'] == 'APPROVED') ? 'P' : 'F';
    $pp_response['reason_text'] = $_REQUEST['approval_code'];
    $pp_response['transaction_id'] = substr($_REQUEST['approval_code'], strrpos(substr($_REQUEST['approval_code'], 0, strlen($_REQUEST['approval_code']) - 1), ":") + 1);
    $pp_response['transaction_id'] = rtrim($pp_response['transaction_id'], ":");

    if (!empty($_REQUEST['failReason'])) {
        $pp_response['reason_text'] .= " Error: " . $_REQUEST['failReason'];
    }

    if (fn_check_payment_script('firstdata_connect.php', $_REQUEST['order_id'])) {
        fn_finish_payment($_REQUEST['order_id'], $pp_response, false);
        fn_order_placement_routines('route', $_REQUEST['order_id']);
    }

} else {
    if ($processor_data['processor_params']['test'] == 'LIVE') {
        $post_address = "https://secure.linkpt.net/lpcentral/servlet/lppay";
    } else {
        $post_address = "https://www.staging.linkpointcentral.com/lpc/servlet/lppay";
    }
    $_order_id = (($order_info['repaid']) ? ($order_id .'_'. $order_info['repaid']) : $order_id) . '_' . fn_date_format(time(), '%H_%M_%S');

    $response_url = fn_url("payment_notification&payment=firstdata_connect&order_id=$order_id", AREA, 'http');

echo <<<EOT
    <form action="$post_address" method="post" name="process">
    <input type="hidden" name="responseURL" value="$response_url">


    <input type="hidden" name="storename" value="{$processor_data['processor_params']['store']}">
    <input type="hidden" name="chargetotal" value="{$order_info['total']}">
    <input type="hidden" name="txnorg" value="eci">
    <input type="hidden" name="mode" value="fullpay">
    <input type="hidden" name="txntype" value="{$processor_data['processor_params']['transaction_type']}">
    <input type="hidden" name="bname" value="{$order_info['firstname']} {$order_info['lastname']}">
    <input type="hidden" name="oid" value="{$processor_data['processor_params']['prefix']}{$_order_id}">

    <input type="hidden" name="baddr1" value="{$order_info['b_address']}">
    <input type="hidden" name="baddr2" value="{$order_info['b_address_2']}">
    <input type="hidden" name="bcity" value="{$order_info['b_city']}">
    <input type="hidden" name="bstate" value="{$order_info['b_state']}">
    <input type="hidden" name="bcountry" value="{$order_info['b_country']}">
    <input type="hidden" name="bzip" value="{$order_info['b_zipcode']}">

    <input type="hidden" name="sname" value="{$order_info['firstname']} {$order_info['lastname']}">
    <input type="hidden" name="saddr1" value="{$order_info['s_address']}">
    <input type="hidden" name="saddr2" value="{$order_info['s_address_2']}">
    <input type="hidden" name="scity" value="{$order_info['s_city']}">
    <input type="hidden" name="sstate" value="{$order_info['s_state']}">
    <input type="hidden" name="scountry" value="{$order_info['s_country']}">
    <input type="hidden" name="szip" value="{$order_info['s_zipcode']}">

    <input type="hidden" name="phone" value="{$order_info['phone']}">
    <input type="hidden" name="fax" value="{$order_info['fax']}">
    <input type="hidden" name="email" value="{$order_info['email']}">
    </form>
EOT;

$msg = __('text_cc_processor_connection', array(
    '[processor]' => 'Linkpoint server'
));
echo <<<EOT
    <p><div align=center>{$msg}</div></p>
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
