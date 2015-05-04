{assign var="average_rating" value=$object_id|fn_get_average_rating:$object_type}

{if $average_rating}
{include file="addons/discussion/views/discussion/components/stars.tpl" stars=$average_rating|fn_get_discussion_rating is_link=true}
{/if}