{if !$google_info.risk_information}

{if $google_actions.charge}
    <div class="hidden" title="{__("payments.gc.charged_amount")}" id="content_google_charge_form">
        <form action="{""|fn_url}" method="post" name="google_charge_form" class="form-horizontal form-edit cm-disable-empty">
            <input type="hidden" name="order_id" value="{$smarty.request.order_id}" />
            <input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />
                <div class="control-group">
                    <label for="elmg_ca_amount" class="control-label cm-required">{__("amount")}</label>
                    <div class="controls">
                        {math equation="a-b" a=$order_info.total b=$google_info.charged_amount|default:0 assign="amount_to_charge"}
                        <input type="text" id="elmg_ca_amount" name="google_data[charge_amount]" size="5" value="{$amount_to_charge}" />
                    </div>
                </div>
                <div class="buttons-container">
                    {include file="buttons/button.tpl" but_text=__("charge") but_name="dispatch[orders.google.charge]" but_meta="btn-primary"}
                </div>
        </form>
    <!--content_google_charge_form--></div>
{/if}

{if $google_actions.refund}
    <div class="hidden" title="{__("zpayments.gc.refund")}" id="content_google_refund_form">
        <form action="{""|fn_url}" method="post" name="google_refund_form" class="form-horizontal form-edit cm-disable-empty">
            <input type="hidden" name="order_id" value="{$smarty.request.order_id}" />
            <input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />

            <div class="control-group">
                <label for="elmg_r_amount" class="control-label cm-required">{__("amount")}:</label>
                <div class="controls">
                    {math equation="a-b" a=$google_info.charged_amount|default:0 b=$google_info.refunded_amount|default:0 assign="amount_to_refund"}
                    <input type="text" id="elmg_r_amount" name="google_data[refund_amount]" size="5" value="{$amount_to_refund}" />
                </div>
            </div>
            <div class="control-group">
                <label for="elmg_r_reason" class="control-label cm-required">{__("reason")}:</label>
                <div class="controls">
                    <input type="text" id="elmg_r_reason" name="google_data[refund_reason]" size="45" value="" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elmg_r_comment">{__("comments")}</label>
                <div class="controls">
                    <input type="text" id="elmg_r_comment" name="google_data[refund_comment]" size="45" value="" />
                </div>
            </div>

            <div class="buttons-container">
                {include file="buttons/button.tpl" but_text=__("refund") but_name="dispatch[orders.google.refund]" but_meta="btn-primary"}
            </div>

        </form>
    <!--content_google_refund_form--></div>
{/if}

{if $google_actions.cancel}
    <div class="hidden" title="{__("cancel")}" id="content_google_cancel_form">
        <form action="{""|fn_url}" method="post" name="google_cancel_form" class="form-horizontal form-edit cm-disable-empty">
            <input type="hidden" name="order_id" value="{$smarty.request.order_id}" />
            <input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />

            <div class="control-group">
                <label for="elm_g_c_reason" class="control-label cm-required">{__("reason")}:</label>
                <div class="controls">
                    <input type="text" id="elm_g_c_reason" name="google_data[cancel_reason]" size="45" value="" />
                </div>
            </div>

            <div class="control-group">
                <label for="elmg_c_comment" class="control-label">{__("comments")}:</label>
                <div class="controls">
                    <input type="text" id="elmg_c_comment" name="google_data[cancel_comment]" size="45" value="" />
                </div>
            </div>

            <div class="buttons-container">
                {include file="buttons/button.tpl" but_text=__("cancel") but_name="dispatch[orders.google.cancel]" but_meta="btn-primary"}
            </div>

        </form>
    <!--content_google_cancel_form--></div>    
{/if}

    <div class="hidden" title="{__("payments.gc.message")}" id="content_google_message_form">
        <form action="{""|fn_url}" method="post" name="google_message_form" class="form-horizontal form-edit cm-disable-empty">
            <input type="hidden" name="order_id" value="{$smarty.request.order_id}" />
            <input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />

            <div class="control-group">
                <label class="cm-required control-label" for="elmg_message">{__("message")}:</label>
                <div class="controls">
                    <textarea class="input-large" id="elmg_message" name="google_data[message]" cols="45" rows="4"></textarea>
                </div>
            </div>

            <div class="buttons-container">
                {include file="buttons/button.tpl" but_text=__("send_message") but_name="dispatch[orders.google.send_message]" but_meta="btn-primary"}
            </div>
        </form>
    <!--content_google_message_form--></div>

{capture name="adv_tools"}
    <li class="divider"></li>
    {if $google_actions.charge}
        <li>{include file="common/popupbox.tpl" id="google_charge_form" act="link" text=__("payments.gc.charged_amount")}</li>
    {/if}

    <li>{include file="common/popupbox.tpl" id="google_message_form" act="link" link_text=__("payments.gc.message") text=__("payments.gc.message")}</li>

    {if $google_actions.refund}
        <li>{include file="common/popupbox.tpl" id="google_refund_form" act="link" link_text=__("payments.gc.refund") text=__("payments.gc.refund")}</li>
    {/if}

    {if $google_actions.cancel}
        <li>{include file="common/popupbox.tpl" id="google_cancel_form" act="link" link_text=__("cancel") text=__("cancel")}</li>
    {/if}

{/capture}

{/if}