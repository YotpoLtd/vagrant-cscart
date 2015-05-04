{assign var="r_url" value="https"|fn_payment_url:"google_checkout_response.php"}
<p>{__("text_google_notice", ["[return_url]" => $r_url])}</p>
<hr>
<fieldset>
<div class="control-group">
    <label class="control-label" for="agreement">{__("accept_google_policy")} :</label>
     <div class="controls">
        <input type="hidden" name="payment_data[processor_params][policy_agreement]" value="N">
        <input type="checkbox" name="payment_data[processor_params][policy_agreement]" value="Y" {if $processor_params.policy_agreement == "Y"}checked="checked"{/if} id="agreement">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="merchant_id">{__("merchant_id")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_id]" id="merchant_id" value="{$processor_params.merchant_id}" size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="merchant_key">{__("merchant_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_key]" id="merchant_key" value="{$processor_params.merchant_key}" size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">
            <option value="USD" {if $processor_params.currency == "USD"}selected="selected"{/if}>{__("currency_code_usd")}</option>
            <option value="GBP" {if $processor_params.currency == "GBP"}selected="selected"{/if}>{__("currency_code_gbp")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="gc_auto_charge">{__("gc_auto_charge")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][gc_auto_charge]" value="N">
        <input type="checkbox" name="payment_data[processor_params][gc_auto_charge]" id="gc_auto_charge" value="Y" {if $processor_params.gc_auto_charge == "Y"}checked="checked"{/if}>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="test">{__("test_live_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][test]" id="test">
            <option value="N" {if $processor_params.test == "N"}selected="selected"{/if}>{__("live")}</option>
            <option value="Y" {if $processor_params.test == "Y"}selected="selected"{/if}>{__("test")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="button_type">{__("button_type")}:</label>
     <div class="controls">
        <select name="payment_data[processor_params][button_type]" id="button_type">
            <option value="white" {if $processor_params.button_type == "white"}selected="selected"{/if}>{__("white")}</option>
            <option value="trans" {if $processor_params.button_type == "trans"}selected="selected"{/if}>{__("transparent")}</option>
        </select>
     </div>
</div>

</fieldset>

{include file="common/subheader.tpl" title="{__("default_values")}: {__("shipping")}" target="#default_values_shipping"}
<div id="default_values_shipping" class="in collapse">
    <fieldset>
        {foreach from="0"|fn_get_shippings item="_ship" key="ship_id"}
            <div class="control-group">
                <label class="control-label" for="shipping_{$ship_id}">{$_ship.shipping}:</label>
                <div class="controls">
                    {assign var="ship_id" value=$_ship.shipping_id}
                    {$currencies.$primary_currency.symbol nofilter} <input type="text" name="payment_data[processor_params][default_shippings][{$ship_id}]" value="{if $processor_params.default_shippings}{$processor_params.default_shippings.$ship_id}{/if}" id="shipping_{$ship_id}" size="10">
                </div>
            </div>
        {foreachelse}
            <p>{__("no_data")}</p>
        {/foreach}
    </fieldset>
</div>

{include file="common/subheader.tpl" title="{__("default_values")}: {__("taxes")}" target="#default_values_taxes"}
<div id="default_values_taxes" class="in collapse">
    <fieldset>
        {assign var="tax_exist" value=false}
        {foreach from=""|fn_get_taxes item="_tax" key="tax_id"}
        {if $_tax.price_includes_tax != "Y"}
        {assign var="tax_exist" value=true}
        <div class="control-group">
            <label class="control-label" for="tax_{$tax_id}">{__("tax")} [{$_tax.tax}]</label>
            <div class="controls">
                {assign var="tax_id" value=$_tax.tax_id}
                <input type="text" name="payment_data[processor_params][default_taxes][{$tax_id}]" value="{if $processor_params.default_taxes}{$processor_params.default_taxes.$tax_id}{/if}" size="10" id="tax_{$tax_id}"> %
            </div>
        </div>
        {/if}
        {/foreach}
        
        {if !$tax_exist}
        <p>{__("no_data")}</p>
        {/if}
    </fieldset>
</div>

<input type="hidden" name="payment_data[processor_params][button]" value='<input type="image" name="Google Checkout" alt="Google Checkout"   src="http://checkout.google.com/buttons/checkout.gif?merchant_id=1234567890&w=160&h=43&style=white&variant=text&loc=en_US" height="43" width="160">'>
