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

    if ($mode == 'result') {
        if (fn_check_payment_script('ideal_basic.php', $_REQUEST['order_id'])) {
            $order_info = fn_get_order_info($_REQUEST['order_id'], true);
            if ($order_info['status'] == 'N') {
                fn_change_order_status($_REQUEST['order_id'], 'O', '', false);
            }
        }
        fn_order_placement_routines('route', $_REQUEST['order_id']);

    } elseif ($mode == 'cancel') {
        if (fn_check_payment_script('ideal_basic.php', $_REQUEST['order_id'])) {
            $pp_response = array();
            $pp_response['order_status'] = 'N';
            $pp_response['reason_text']  = __('text_transaction_cancelled');
            fn_finish_payment($_REQUEST['order_id'], $pp_response);
        }
        fn_order_placement_routines('route', $_REQUEST['order_id'], false);

    } else {

        $xml_response = !isset($GLOBALS['HTTP_RAW_POST_DATA']) ? file_get_contents("php://input") : $GLOBALS['HTTP_RAW_POST_DATA'];

        if (!empty($xml_response)) {
            preg_match("/<transactionID>(.*)<\/transactionID>/", $xml_response, $transaction);
            preg_match("/<purchaseID>(.*)<\/purchaseID>/", $xml_response, $purchase);
            preg_match("/<status>(.*)<\/status>/", $xml_response, $status);
            preg_match("/<createDateTimeStamp>(.*)<\/createDateTimeStamp>/", $xml_response, $date);

            $order_id = (strpos($purchase[1], '_')) ? substr($purchase[1], 0, strpos($purchase[1], '_')) : $purchase[1];
            $pp_response = array();

            if ($status[1] == 'Success') {
                $pp_response['order_status'] = 'P';

            } elseif ($status[1] == 'Open') {
                $pp_response['order_status'] = 'O';

            } elseif ($status[1] == 'Cancelled') {
                $pp_response['order_status'] = 'I';

            } else {
                $pp_response['order_status'] = 'F';
            }

            $pp_response['reason_text'] = "Status code: " . $status[1];

            $dat = $date[1];
            $time = $dat[0] . $dat[1] . $dat[2] . $dat[3] . '-' . $dat[4] . $dat[5] . '-' . $dat[6] . $dat[7] . ' ' . $dat[8] . $dat[9] . ':' . $dat[10] . $dat[11] . ':' . $dat[12] . $dat[13];

            $pp_response['reason_text'].= " (TimeStamp: ".$time.")";

            $pp_response['transaction_id'] = $transaction[1];
            if (fn_check_payment_script('ideal_basic.php', $order_id)) {
                fn_finish_payment($order_id, $pp_response); // Force customer notification
            }
        }
    }

} else {

    $langs = array(
        "US" => "en_US",
        "FR" => "fr_FR",
        "NL" => "nl_NL",
        "IT" => "it_IT",
        "DE" => "de_DE",
        "ES" => "es_ES",
        "NO" => "no_NO",
        "en" => "en_EN"
    );

$post = array();

$post['return'] = fn_url("payment_notification.result?payment=ideal_basic&order_id=$order_id", AREA, 'current');
$post['cancel'] = fn_url("payment_notification.cancel?payment=ideal_basic&order_id=$order_id", AREA, 'current');

$validUntil = date("Y-m-d\TH:i:s", time() + 3600 + date('Z'));
$validUntil = $validUntil . ".000Z";
$pp_merch = $processor_data['processor_params']['merchant_id'];
$pp_secret = $processor_data['processor_params']['merchant_key'];
$pp_curr = $processor_data['processor_params']['currency'];
$pp_test = ($processor_data['processor_params']['test'] == 'TRUE') ? "https://idealtest.secure-ing.com/ideal/mpiPayInitIng.do" : "https://ideal.secure-ing.com/ideal/mpiPayInitIng.do";
$pp_lang = $processor_data['processor_params']['language'];
$order_total = $order_info['total'] * 100;
$_order_id = ($order_info['repaid']) ? ($order_id .'_'. $order_info['repaid']) : $order_id;

/*$shastring = "$key" . "$merchantID" . "$subID" . "$amount" . "$orderNumber" .
"$paymentType" . "$validUntil" .
"$itemNumber1" . "$itemDescription1" . $product1number . $product1price .
"$itemNumber2" . "$itemDescription2" . $product2number . $product2price .
"$itemNumber3" . "$itemDescription3" . $product3number . $product3price .
"$itemNumber4" . "$itemDescription4" . $product4number . $product4price;

concatString = merchantKey + merchantID + subID + amount + purchaseID + paymentType + validUntil + itemNumber1 + itemDescription1 + itemQuantity1
+ itemPrice1 (+ itemNumber2 + itemDescription2 + itemQuantity2 + itemPrice2 + itemNumber3 + item...)*/

$pre_sha = '';
$total = 0;
// Products
if (!empty($order_info['products'])) {
    foreach ($order_info['products'] as $k => $v) {
        $_name = str_replace('"', "", str_replace("'", "", $v['product']));
        $pre_sha = $pre_sha . $v['product_id'] . $_name . $v['amount'] . (fn_format_price(($v['subtotal'] - fn_external_discounts($v)) / $v['amount']) * 100);
        $total = $total + (fn_format_price($v['subtotal'] - fn_external_discounts($v)) * 100);
    }
}
// Gift Certificates
if (!empty($order_info['gift_certificates'])) {
    foreach ($order_info['gift_certificates'] as $k => $v) {
        $v['amount'] = (!empty($v['extra']['exclude_from_calculate'])) ? 0 : $v['amount'];
        $pre_sha = $pre_sha . $v['gift_cert_id'] . $v['gift_cert_code'] . '1' . ($v['amount'] * 100);
        $total = $total + $v['amount'] * 100;
    }
}
if ($total < $order_total) {
    $pre_sha = $pre_sha . "SH" . "Shipping" . "1" . ($order_total - $total);
}

$shastring = "$pp_secret"."$pp_merch"."0"."$order_total"."$_order_id"."ideal"."$validUntil".$pre_sha;

$shastring = str_replace(" ", "", $shastring);
$shastring = str_replace("\t", "", $shastring);
$shastring = str_replace("\n", "", $shastring);
$shastring = str_replace("&amp;", "&", $shastring);
$shastring = str_replace("&lt;", "<", $shastring);
$shastring = str_replace("&gt;", ">", $shastring);
$shastring = str_replace("&quot;", "\"", $shastring);

$shasign = sha1($shastring);

$counter = 1;
$com = 0; // FIXME 24.11.2009 Variable is not used.

echo <<<EOT
    <form method="post" action="{$pp_test}" name="process">
        <input type="hidden" NAME="merchantID" value="{$pp_merch}">
        <input type="hidden" NAME="subID" value="0">
        <input type="hidden" NAME="amount" VALUE="{$order_total}" >
        <input type="hidden" NAME="purchaseID" VALUE="{$_order_id}">
        <input type="hidden" NAME="language" VALUE="{$pp_lang}">
        <input type="hidden" NAME="currency" VALUE="EUR">
        <input type="hidden" NAME="description" VALUE="iDEAL Basic purchase">
        <INPUT type="hidden" NAME="hash" VALUE="{$shasign}">
        <input type="hidden" NAME="paymentType" VALUE="ideal">
        <input type="hidden" NAME="validUntil" VALUE="$validUntil">

EOT;
// Products
if (!empty($order_info['products'])) {
    foreach ($order_info['products'] as $k => $v) {
        $am = fn_format_price($v['subtotal'] - fn_external_discounts($v)) * 100;
        $pr = fn_format_price(($v['subtotal'] - fn_external_discounts($v))/$v['amount']) * 100;
        $_name = str_replace('"', "", str_replace("'", "", $v['product']));
echo <<<EOT
        <INPUT type="hidden" NAME="itemNumber$counter" VALUE="{$v['product_id']}">
        <INPUT type="hidden" NAME="itemDescription$counter" VALUE="{$_name}">
        <INPUT type="hidden" NAME="itemQuantity$counter" VALUE="{$v['amount']}">
        <INPUT type="hidden" NAME="itemPrice$counter" VALUE="{$pr}">

EOT;
        $com = $com + $am;
        $counter++;
    }
}
// Gift Cartificates
if (!empty($order_info['gift_certificates'])) {
    foreach ($order_info['gift_certificates'] as $k => $v) {
        $am = (!empty($v['extra']['exclude_from_calculate'])) ? 0 : ($v['amount'] * 100);
echo <<<EOT
        <INPUT type="hidden" NAME="itemNumber$counter" VALUE="{$v['gift_cert_id']}">
        <INPUT type="hidden" NAME="itemDescription$counter" VALUE="{$v['gift_cert_code']}">
        <INPUT type="hidden" NAME="itemQuantity$counter" VALUE="1">
        <INPUT type="hidden" NAME="itemPrice$counter" VALUE="{$am}">

EOT;
        $com = $com + $am;
        $counter++;
    }
}
// Shipping
if ($total < $order_total) {
    $ship = $order_total - $total;
echo <<<EOT
        <INPUT type="hidden" NAME="itemNumber$counter" VALUE="SH">
        <INPUT type="hidden" NAME="itemDescription$counter" VALUE="Shipping">
        <INPUT type="hidden" NAME="itemQuantity$counter" VALUE="1">
        <INPUT type="hidden" NAME="itemPrice$counter" VALUE="{$ship}">

EOT;
}

$msg = __('text_cc_processor_connection', array(
    '[processor]' => 'iDEAL server'
));

echo <<<EOT
        <input type="hidden" NAME="urlCancel" VALUE="{$post['cancel']}">
        <input type="hidden" NAME="urlSuccess" VALUE="{$post['return']}">
        <input type="hidden" NAME="urlError" VALUE="{$post['return']}">
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
exit;
}
