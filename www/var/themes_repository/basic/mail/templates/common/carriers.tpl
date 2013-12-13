{hook name="carriers:list"}

{if $carrier == "usps"}
    {$url = "http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?strOrigTrackNum=`$tracking_number`"}
    {$carrier_name = __("usps")}
{elseif $carrier == "ups"}
    {$url = "http://wwwapps.ups.com/WebTracking/processInputRequest?AgreeToTermsAndConditions=yes&amp;tracknum=`$tracking_number`"}
    {$carrier_name = __("ups")}
{elseif $carrier == "fedex"}
    {$url = "http://fedex.com/Tracking?action=track&amp;tracknumbers=`$tracking_number`"}
    {$carrier_name = __("fedex")}
{elseif $carrier == "aup"}
    <form name="tracking_form{$shipment_id}" target="_blank" action="http://ice.auspost.com.au/display.asp?ShowFirstScreenOnly=FALSE&amp;ShowFirstRecOnly=TRUE" method="post">
        <input type="hidden"  name="txtItemNumber" maxlength="13" value="{$tracking_number}" />
    </form>
    {$url = "javascript: document.tracking_form`$shipment_id`.submit();"}
    {$carrier_name = __("australia_post")}
{elseif $carrier == "dhl" || $shipping.carrier == "ARB"}
    <form name="tracking_form{$shipment_id}" target="_blank" method="post" action="http://track.dhl-usa.com/TrackByNbr.asp?nav=Tracknbr">
        <input type="hidden" name="txtTrackNbrs" value="{$tracking_number}" />
    </form>
    {$url = "javascript: document.tracking_form`$shipment_id`.submit();"}
    {$carrier_name = __("dhl")}
{elseif $carrier == "swisspost"}
    {$url = "http://www.post.ch/swisspost-tracking?formattedParcelCodes=`$tracking_number`"}
    {$carrier_name = __("chp")}
{else}
    {$url = ""}
    {$carrier_name = $carrier}
{/if}

{/hook}

{capture name="carrier_name"}
{$carrier_name}
{/capture}

{capture name="carrier_url"}
{$url nofilter}
{/capture}