{include file="common/letter_header.tpl"}

{__("hello")} {$send_data.to_name},<br /><br />

{__("text_recommendation_notes")}<br />
<a href="{$link|fn_url:'C'}">{$link|fn_url:'C'}</a><br /><br />
<b>{__("notes")}:</b><br />
{$send_data.notes|replace:"\n":"<br />" nofilter}

{include file="common/letter_footer.tpl"}