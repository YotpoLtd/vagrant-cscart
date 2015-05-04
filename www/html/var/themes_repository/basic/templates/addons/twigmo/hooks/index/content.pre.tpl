{$but_role = "general"}
{$addon_images_path = "$path_rel/media/images/addons/twigmo/images/"}

{capture "mobile_store_link"}
	{if {strpos($config.current_url, "?")}}
		{$but_href = fn_url("`$config.current_url`&auto"|fn_query_remove:"desktop")}
	{else}
		{$but_href = fn_url("`$config.current_url`?auto"|fn_query_remove:"desktop")}
	{/if}
	{*
		Workaround for IIS when SEO is enabled, cause site.com/?param doesn't work,
		but site.com/index.php?param works fine.
        When SEO is enabled, index.php is cut from URL
    *}
	    {if defined("HTTPS")}
	    	{$home_location = "https://`$config.https_host``$config.https_path`"}
	    {else}
	    	{$home_location = "http://`$config.http_host``$config.http_path`"}
	    {/if}
	    {$offset=strlen($home_location)}
	    {if substr($but_href, $offset, 6) == "/?auto"}
	        {$but_href="`$home_location`/`$config.customer_index`?auto"}
	    {/if}
    {* /for IIS *}
	<a href="{$but_href}">{__('twg_visit_our_mobile_store')}</a>
{/capture}

{capture "android"}
	{$but_href = $tw_settings.url_on_googleplay|default:"https://play.google.com/store"}
	<a href="{$but_href}">{__('twg_app_for_android')}</a>
{/capture}

{capture "ios"}
	{$but_href = $tw_settings.url_on_appstore|default:"https://itunes.apple.com/en/genre/ios/id36?mt=8"}
	{if $smarty.session.device == "iphone"}
		{$but_text = __('twg_app_for_iphone')}
	{elseif $smarty.session.device == "ipad"}
	    {$but_text = __('twg_app_for_ipad')}
	{/if}
	<a href="{$but_href}">{$but_text}</a>
{/capture}

{capture "notice_block"}
<div class="mobile-avail-notice{if $smarty.session.twigmo_mobile_avail_notice_off} hidden{/if}">
	<div class="buttons-container">
		{$smarty.capture.mobile_store_link nofilter}
		{if $smarty.session.device == "android" and $tw_settings.url_on_googleplay}
			{$smarty.capture.android nofilter}
		{elseif ($smarty.session.device == "iphone" or $smarty.session.device == "ipad") and $tw_settings.url_on_appstore}
			{$smarty.capture.ios nofilter}
		{/if}
		<img id="close_notification_mobile_avail_notice" class="cm-notification-close hand" src="{$addon_images_path}icons/icon_close.png" border="0" alt="Close" title="Close" />
	</div>
</div>
{/capture}

{if ($smarty.session.twg_user_agent and $smarty.session.twg_user_agent == 'tablet' and $tw_settings.use_mobile_frontend == 'tablet')
		or ($smarty.session.twg_user_agent and $smarty.session.twg_user_agent == 'phone' and $tw_settings.use_mobile_frontend == 'phone')
		or ($smarty.session.twg_user_agent and ($smarty.session.twg_user_agent == 'tablet' or $smarty.session.twg_user_agent == 'phone') and $tw_settings.use_mobile_frontend == 'both_tablet_and_phone')}
	{$show_avail_notice = "Y"}
{else}
	{$show_avail_notice = "N"}
{/if}

{if $tw_settings.use_mobile_frontend != 'never' and $show_avail_notice == "Y"}
	{if $smarty.session.device == "iphone" or $smarty.session.device == "ipad" or $smarty.session.device == "android" or $smarty.session.device == "winphone"}
		{$smarty.capture.notice_block nofilter}
	{/if}
{/if}
