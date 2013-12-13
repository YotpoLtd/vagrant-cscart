<div class="sidebar-row">
<h6>{__("search")}</h6>

<form action="{""|fn_url}" name="addons_search_form" method="get" class="{$form_meta}">
{capture name="simple_search"}

{$extra nofilter}

<div class="sidebar-field">
    <label for="elm_addon">{__("name")}</label>
    <input type="text" name="q" id="elm_addon" value="{$search.q}" size="30" />
</div>

{/capture}

{capture name="advanced_search"}

{hook name="addons:advanced_search"}
<div class="row-fluid">
    <div class="group span6 form-horizontal">
        <div class="control-group">
            <label class="control-label" for="elm_addon_type">{__("type")}</label>
            <div class="controls">
            <select name="type" id="elm_addon_type">
                <option value="any">--</option>
                <option value="active" {if $search.type == "active"}selected="selected"{/if}>{__("active")}</option>
                <option value="disabled" {if $search.type == "disabled"}selected="selected"{/if}>{__("disabled")}</option>
                <option value="installed" {if $search.type == "installed"}selected="selected"{/if}>{__("installed")}</option>
                <option value="not_installed" {if $search.type == "not_installed"}selected="selected"{/if}>{__("not_installed")}</option>            
            </select>
            </div>
        </div>
    </div>
</div>
{/hook}

{hook name="addons:search_form"}
{/hook}

{/capture}

{include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search advanced_search=$smarty.capture.advanced_search dispatch=$dispatch view_type="addons"}

</form>
</div>

