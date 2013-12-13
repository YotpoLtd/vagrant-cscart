<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$smarty.const.CART_LANGUAGE|lower}" xml:lang="{$smarty.const.CART_LANGUAGE|lower}">
<head>
{if $twg_settings.companyName}
    <title>{if $twg_settings.home_page_title}{$twg_settings.home_page_title} - {/if}{$twg_settings.companyName}</title>
{else}
    <title>{$twg_settings.home_page_title}</title>
{/if}
<meta content="Twigmo" name="description" />
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0">

<meta name="MobileOptimized" content="width" />
<meta name="HandheldFriendly" content="true" />

<link rel="apple-touch-icon" href="{$favicon_url}" />
<link rel="shortcut icon" href="{$favicon_url}" />

<link rel="stylesheet" type="text/css" href="{$mobile_scripts_url}/lib/jquery/jquery.mobile.custom.structure.min.css">
<link rel="stylesheet" type="text/css" href="{$mobile_skin_path}/app-compiled.css" />

<!--[if IE]>
<link rel="stylesheet" type="text/css" href="{$mobile_skin_path}/ie-compiled.css" />
<![endif]-->

{if $custom_css_file_path && $custom_css_file_exists}
    <link rel="stylesheet" type="text/css" href="{$custom_css_file_path}" />
{/if}

<script src="{$mobile_scripts_url}/lib/jquery/jquery.js"></script>
<script>
//<![CDATA[
{literal}
    $(document).bind("mobileinit", function () {
        $.mobile.pushStateEnabled = $.mobile.ajaxEnabled = false;
        $.mobile.ignoreContentEnabled=true;
    });
{/literal}
//]]>
</script>
<script src="{$mobile_scripts_url}/lib/jquery/jquery.mobile.custom.min.js"></script>

<script src="{$mobile_scripts_url}/lib.js"></script>
<script src="{$mobile_scripts_url}/twigmo-compiled.js"></script>

{if $twg_settings.geolocation == 'Y'}
    <script src="http{if "HTTPS"|defined}s{/if}://maps.google.com/maps/api/js?sensor=false&?v=3.7&language={$smarty.const.CART_LANGUAGE}"></script>
{/if}

<script>
//<![CDATA[
    var settings = {$json_twg_settings nofilter};
//]]>
</script>

</head>
<body id= "main-body" class="template-mobilefrontpage portaltype-plone-site icons-on" dir="ltr"></body>
{if $addons.google_analytics.status == "A"}
    {include file="addons/google_analytics/hooks/index/footer.post.tpl"}
{/if}
</html>
