{hook name="index:meta"}
{if $display_base_href}
<base href="{$config.current_location}/" />
{/if}
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<meta http-equiv="Content-Type" content="text/html; charset={$smarty.const.CHARSET}" />
{hook name="index:meta_description"}
<meta name="description" content="{$meta_description|html_entity_decode:$smarty.const.ENT_COMPAT:"UTF-8"|default:$location_data.meta_description}" />
{/hook}
<meta name="keywords" content="{$meta_keywords|default:$location_data.meta_keywords}" />
<meta name="mode" content="{$store_trigger}" />
{/hook}