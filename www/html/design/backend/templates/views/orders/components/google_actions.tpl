{include file="common/subheader.tpl" title=__("information")}

{foreach from=$google_info key="name" item="value"}
    <label>{__($name)}: {$value}</label>
{/foreach}
</br>
{if !$google_info.risk_information}
<input type="hidden" name="order_id" value="{$smarty.request.order_id}" />
<input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />

{if $google_info.fulfillment_state == "NEW" || $google_info.fulfillment_state == "PROCESSING"}
    {include file="buttons/button.tpl" but_text=__("deliver") but_name="dispatch[orders.google.deliver]"}
{/if}

{if $google_info.fulfillment_state == "DELIVERED" || $google_info.financial_state == "CANCELLED" || $google_info.financial_state == "CANCELLED_BY_GOOGLE"}
    {include file="buttons/button.tpl" but_text=__("archive") but_name="dispatch[orders.google.archive]"}
{/if}

{include file="buttons/button.tpl" but_text=__("add_tracking_data") but_name="dispatch[orders.google.add_tracking_data]"}

{/if}