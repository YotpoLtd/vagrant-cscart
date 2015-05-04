{if !"ULTIMATE"|fn_allowed_for && !$runtime.company_id}
{include file="common/subheader.tpl" title=__("seo") target="#acc_addon_seo"}
<div id="acc_addon_seo" class="collapsed in">
	<div class="control-group">
	    <label class="control-label" for="company_seo_name">{__("seo_name")}:</label>
	    <div class="controls">
	    	<input type="text" name="company_data[seo_name]" id="company_seo_name" size="55" value="{$company_data.seo_name}" class="input-large" />
	    </div>
	</div>
</div>
{/if}