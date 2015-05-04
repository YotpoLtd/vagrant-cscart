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
    if ($mode == 'notify') {
        $order_id = $_REQUEST['order_id'];
        $order_info = fn_get_order_info($order_id);

        $pp_response = array(
            'reason_text' => '',
            'order_status' => 'F'
        );
        if (!empty($_REQUEST['mm_status'])) {
            $pp_response['order_status'] = ($_REQUEST['mm_status'] == 'success') ? "P" : "D";
            $pp_response['reason_text'] .= "Status: $_REQUEST[mm_status]; ";
        }

        if (!empty($_REQUEST['mm_transid'])) {
            $pp_response['transaction_id'] = $_REQUEST['mm_transid'];
        }
        if (!empty($_REQUEST['mm_checkNo'])) {
            $pp_response['reason_text'] .= "CheckNumber: $_REQUEST[mm_checkNo]; ";
            if ($order_info['payment_info']['check_number'] != $_REQUEST['mm_checkNo']) {
                $pp_response['order_status'] = 'F';
                $pp_response['reason_text'] .= 'CheckNumber does not match; ';
            }

        }
        if (!empty($_REQUEST['mm_msg'])) {
            $pp_response['reason_text'] .= "Reason: $_REQUEST[mm_msg]; ";
        }
        if (!empty($_REQUEST['mm_excp'])) {
            $pp_response['reason_text'] .= "Exception: $_REQUEST[mm_excp]; ";
        }
        if (!empty($_REQUEST['mm_code'])) {
            $pp_response['reason_text'] .= "ErrorCode: $_REQUEST[mm_code]; ";
        }

        if (fn_check_payment_script('p21_simple_api.php', $order_id)) {
            fn_finish_payment($order_id, $pp_response);
            fn_order_placement_routines('route', $order_id);
        }
    }

} else {
    $_order_id = ($order_info['repaid']) ? ($order_id .'_'. $order_info['repaid']) : $order_id;
    $return_url = fn_url("payment_notification.notify?payment=p21_simple_api&order_id=$order_id", AREA, 'current');
    $birth_date = date("m/d/Y", fn_parse_date($order_info['payment_info']['date_of_birth']));

echo <<<EOT
<form action="https://www.payment21.com/interfaces/mmltdonline/p21paybycheck/default.aspx" method="POST" name="process">
    <input type="hidden" name="mm_userid" value="{$processor_data['processor_params']['merchant_id']}" />
    <input type="hidden" name="mm_pwd" value="{$processor_data['processor_params']['password']}" />
    <input type="hidden" name="mm_ip_address" value="{$processor_data['processor_params']['ip_address']}" />
    <input type="hidden" name="mm_company" value="{$processor_data['processor_params']['company']}" />
    <input type="hidden" name="mm_redirecturl" value="{$return_url}" />
    <input type="hidden" name="mm_errorurl" value="{$return_url}" />
    <input type="hidden" name="mm_updatedby" value="xxxx" />
    <input type="hidden" name="mm_merchantcustomerid" value="{$order_info['user_id']}" />
    <input type="hidden" name="mm_merchanttransid" value="{$_order_id}" />
    <input type="hidden" name="mm_firstname" value="{$order_info['b_firstname']}" />
    <input type="hidden" name="mm_lastname" value="{$order_info['b_lastname']}" />
    <input type="hidden" name="mm_dateofbirth" value="{$birth_date}" />
    <input type="hidden" name="mm_address" value="{$order_info['b_address']}" />
    <input type="hidden" name="mm_address2" value="{$order_info['b_address_2']}" />
    <input type="hidden" name="mm_last4ssn" value="{$order_info['payment_info']['last4ssn']}" />
    <input type="hidden" name="mm_city" value="{$order_info['b_city']}" />
    <input type="hidden" name="mm_state" value="{$order_info['b_state']}" />
    <input type="hidden" name="mm_zipcode" value="{$order_info['b_zipcode']}" />
    <input type="hidden" name="mm_country" value="{$order_info['b_country']}" />
    <input type="hidden" name="mm_phone" value="{$order_info['payment_info']['phone']}" />
    <input type="hidden" name="mm_email" value="{$order_info['email']}" />
    <input type="hidden" name="mm_amount" value="{$order_info['total']}" />
    <input type="hidden" name="mm_routingcode" value="{$order_info['payment_info']['routing_code']}" />
    <input type="hidden" name="mm_accountnr" value="{$order_info['payment_info']['account_number']}" />
    <input type="hidden" name="mm_checknr" value="{$order_info['payment_info']['check_number']}" />
    <input type="hidden" name="mm_passportnr" value="{$order_info['payment_info']['passport_number']}" />
    <input type="hidden" name="mm_driverlicensenr" value="{$order_info['payment_info']['drlicense_number']}" />
    <input type="hidden" name="mm_agree" value="{$order_info['payment_info']['mm_agree']}" />
    <input type="hidden" name="mm_TOC" value="1" />
EOT;

$msg = __('text_cc_processor_connection', array(
    '[processor]' => 'Payment21 server'
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
die();
}
exit;
