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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {
    if ($mode == 'process' && !empty($_REQUEST['order_id'])) {

        $order_id = $_REQUEST['order_id'];
        $order_info = fn_get_order_info($order_id);
        $processor_data = fn_get_payment_method_data($order_info['payment_id']);

        $request = array(
            'Authentication' => array(
                'Username' => $processor_data['processor_params']['username'],
                'Password' => $processor_data['processor_params']['password'],
                'CustomerID' => $processor_data['processor_params']['merchant_id'],
            ),
            'AccessCode' => $_REQUEST['AccessCode']
        );

        try {
            $client = new SoapClient("https://au.ewaypayments.com/hotpotato/soap.asmx?WSDL", array(
                'trace'      => false,
                'exceptions' => true,
            ));
            $result = $client->GetAccessCodeResult( array('request' => $request) );
        } catch (Exception $e) {
            $lblError = $e->getMessage();
        }

        if (isset($lblError) ) {
            fn_set_notification('E', __('error'), $lblError);
            fn_order_placement_routines('checkout_redirect');
        }

        $pp_response = array(
            'order_status' => 'F',
            'reason_text' => ''
        );

        $response = $result->GetAccessCodeResultResult;

        if (!empty($response->TransactionID)) {
            $pp_response['transaction_id'] = $response->TransactionID;
        }
        $ok_statuses = array('00','08','10','11','16');
        if (in_array($response->ResponseCode, $ok_statuses)) {
            $pp_response['order_status'] = 'P';
        }
        if (!empty($response->AuthorisationCode)) {
            $pp_response['reason_text'] .= "AuthCode: " . $response->AuthorisationCode . "; ";
        }
        if (isset($response->ResponseCode)) {
            $pp_response['reason_text'] .= "ResponseCode: " . $response->ResponseCode . "; ";
        }
        if (!empty($response->ResponseMessage)) {
            $pp_response['reason_text'] .= "ResponseMessage: " . $response->ResponseMessage . "; ";
        }
        if (!empty($response->InvoiceNumber)) {
            $pp_response['reason_text'] .= "InvoiceNumber: " . $response->InvoiceNumber . "; ";
        }
        if (isset($response->BeagleScore)) {
            $pp_response['reason_text'] .= "BeagleScore: " . $response->BeagleScore . "; ";
        }
        if (isset($response->TransactionStatus) && !empty($response->TransactionStatus)) {
            $pp_response['reason_text'] .= "TransactionStatus: " . $response->TransactionStatus . "; ";
        }

        if (fn_check_payment_script('rapidapi.php', $order_id)) {
            fn_finish_payment($order_id, $pp_response, false);
            fn_order_placement_routines('route', $order_id);
        }
    }

} else {

    $soap_url = ($processor_data['processor_params']['mode'] == 'test') ? 'https://au.ewaypayments.com/hotpotato/soap.asmx' : 'https://au.ewaypayments.com/hotpotato/soap.asmx';
    $payment_url = ($processor_data['processor_params']['mode'] == 'test') ? 'https://au.ewaypayments.com/hotpotato/payment' : 'https://au.ewaypayments.com/hotpotato/payment';

    $request = array(
        'Authentication' => array(
            'Username' => $processor_data['processor_params']['username'],
            'Password' => $processor_data['processor_params']['password'],
            'CustomerID' => $processor_data['processor_params']['merchant_id'],
        ),
        'Customer' => array(
            'Title' => !empty($order_info['title']) ? $order_info['title'] : '',
            'FirstName' => $order_info['b_firstname'],
            'LastName' => $order_info['b_lastname'],
            'Street1' => $order_info['b_address'],
            'City' => $order_info['b_city'],
            'State' => $order_info['b_state'],
            'PostalCode' => $order_info['b_zipcode'],
            'Country' => $order_info['b_country'],
            'CompanyName' => $order_info['company'],
            'Email' => $order_info['email'],
            'Phone' => $order_info['b_phone'],
            'SaveToken' => 0,
        ),
        'Payment' => array(
            'TotalAmount' => $order_info['total'] * 100,
            'InvoiceNumber' => $order_id,
            'InvoiceDescription' => __('order') . " #$order_id",
            'InvoiceReference' => $order_id . ($order_info['repaid'] ? "_$order_info[repaid]" : ''),
        ),
        'RedirectUrl' => Registry::get('config.current_location') . '/' . (AREA == 'A' ? Registry::get('config.admin_index') : Registry::get('config.customer_index')) . "?dispatch=payment_notification.process&payment=rapidapi&order_id=$order_id",
        'ResponseMode' => 'Redirect',
        'IPAddress' => $_SERVER['REMOTE_ADDR'],
        'BillingCountry' => strtolower($order_info['b_country'])
    );

    try {
        $client = new SoapClient("https://au.ewaygateway.com/mh/soap.asmx?WSDL", array(
            'trace'      => false,
            'exceptions' => true,
        ));
        $result = $client->CreateAccessCode( array('request' => $request) );
    } catch (Exception $e) {
        $lblError = $e->getMessage();
    }

    if (isset($lblError) ) {
        fn_set_notification('E', __('error'), $lblError);
        fn_order_placement_routines('checkout_redirect');
    }

    $access_code = $result->CreateAccessCodeResult->AccessCode;

    $post_data = array(
        'EWAY_ACCESSCODE' => $access_code,
        'EWAY_CARDNAME' => $order_info['payment_info']['cardholder_name'],
        'EWAY_CARDNUMBER' => $order_info['payment_info']['card_number'],
        'EWAY_CARDMONTH' => $order_info['payment_info']['expiry_month'],
        'EWAY_CARDYEAR' => $order_info['payment_info']['expiry_year'],
        'EWAY_CARDCVN' => $order_info['payment_info']['cvv2'],
    );

    fn_create_payment_form($payment_url, $post_data, 'Rapid API server');
}
exit;
