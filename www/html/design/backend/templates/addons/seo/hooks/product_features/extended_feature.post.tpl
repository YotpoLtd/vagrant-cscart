{if $runtime.company_id && "ULTIMATE"|fn_allowed_for || "MULTIVENDOR"|fn_allowed_for}
<div class="control-group">
    <label class="control-label" for="elm_seo_name_{$id}_{$num}">{__("seo_name")}:</label>
    <div class="controls">
    	<input type="text" name="feature_data[variants][{$num}][seo_name]" id="elm_seo_name_{$id}_{$num}" size="55" value="{if !$empty_string}{$var.seo_name}{/if}" class="span9" />
    </div>
</div>
{/if}