
<form action="{""|fn_url}" method="post" class="form-horizontal form-edit " name="import_locations" enctype="multipart/form-data">

<div class="tabs cm-j-tabs">
    <ul class="nav nav-tabs">
        <li class="cm-js active"><a>{__("general")}</a></li>
    </ul>
</div>

<div class="cm-tabs-content">
    <div class="control-group">
        <label class="control-label">{__("select_file")}</label>
        <div class="controls">
            {include file="common/fileuploader.tpl" var_name="filename[0]"}
        </div>
    </div>

    <div class="control-group cm-no-hide-input">
        <div class="controls">
            <label class="checkbox" for="clean_up_export_{$location_id}">
            <input id="clean_up_export_{$location.location_id}" type="checkbox" name="clean_up" value="1" />
            {__("clean_up_all_locations_on_import")}</label>
        </div>
    </div>

    <div class="control-group cm-no-hide-input">
        <div class="controls">
        <label class="checkbox" for="override_by_dispatch_{$location_id}">
        <input id="override_by_dispatch_{$location.location_id}" type="checkbox" name="override_by_dispatch" value="1" checked="checked" />
        {__("override_by_dispatch")}</label>
        </div>
    </div>
</div>

<div class="buttons-container">
    {include file="buttons/save_cancel.tpl" but_text=__("import") but_name="dispatch[block_manager.import_locations]" cancel_action="close" but_meta="cm-dialog-closer"}
</div>
</form>