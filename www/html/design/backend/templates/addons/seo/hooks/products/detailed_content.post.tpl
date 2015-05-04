{if $runtime.company_id && "ULTIMATE"|fn_allowed_for || "MULTIVENDOR"|fn_allowed_for}
{include file="common/subheader.tpl" title=__("seo") target="#acc_addon_seo"}
<div id="acc_addon_seo" class="collapsed in">
<div class="control-group cm-no-hide-input">
    <label class="control-label" for="product_seo_name">{__("seo_name")}:</label>
    <div class="controls">
    	<input type="text" name="product_data[seo_name]" id="product_seo_name" size="55" value="{$product_data.seo_name}" class="input-large" />
    </div>
</div>
</div>

{/if}