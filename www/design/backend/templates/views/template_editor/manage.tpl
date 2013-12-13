{script src="js/lib/ace/ace.js"}
{script src="js/tygh/template_editor_scripts.js"}
{script src="js/tygh/design_mode.js"}
{script src="js/lib/bootstrap_switch/js/bootstrapSwitch.js"}

{style src="lib/bootstrap_switch/stylesheets/bootstrapSwitch.css"}

<script type="text/javascript">
    //<![CDATA[
    (function (_, $) {
        _.tr({
            text_restore_question : '{__("text_restore_question")|escape:"javascript"}',
            text_enter_filename : '{__("text_enter_filename"|escape:"javascript")}',
            text_are_you_sure_to_delete_file : '{__("text_are_you_sure_to_delete_file"|escape:"javascript")}'
        });

        _.template_editor.company_id = '{$runtime.company_id}';
    }(Tygh, Tygh.$));
    //]]>
</script>

{capture name="mainbox"}

<div id="error_box" class="hidden">
    <div align="center" class="notification-e">
        <div id="error_status"></div>
    </div>
</div>

<div id="status_box" class="hidden">
    <div class="notification-n" align="center">
        <div id="status"></div>
    </div>
</div>

<!--Editor-->
<div class="te-content cm-te-content">
    <div id="template_text"></div>
    <div id="template_image"></div>
</div>

<div class="cm-te-messages">
    <div class="te-not-selected empty-text">
        <h2>{__("nothing_selected")}</h2>
    </div>
    <div class="te-empty-folder empty-text">
        <h2>{__("open_file_or_create_new")}</h2>
        {include file="common/popupbox.tpl" id="add_new_file" text=__("new_file") content=$smarty.capture.add_new_file link_text=__("create_file") act="general" link_class="btn-primary" icon="icon-plus icon-white"}
    </div>
    <div class="te-unknown-file empty-text">
        <h2>{__("could_not_open_file")}</h2>
    </div>
</div>

<div class="buttons-container">
    {capture name="upload_file"}
        <form name="upload_form" action="{""|fn_url}" method="post" enctype="multipart/form-data" class="form-horizontal form-edit">
        <div class="control-group">
            <label for="type_{"uploaded_data[0]"|md5}" class="control-label cm-required">{__("select_file")}</label>
            <div class="controls">
                <input type="hidden" name="fake" value="1" />
                {include file="common/fileuploader.tpl" var_name="uploaded_data[0]"}
                <input type="hidden" name="path" id="upload_path" />
            </div>
        </div>
        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" but_text=__("upload") but_name="dispatch[template_editor.upload_file]" but_meta="cm-te-upload-file" cancel_action="close"}
        </div>
        </form>
    {/capture}

    {capture name="add_new_folder"}
        <form name="add_folder_form" class="form-horizontal cm-form-highlight form-edit">
        <div class="control-group">
            <label for="new_folder" class="control-label cm-required">{__("name")}</label>
            <div class="controls">
                <input type="text" class="span9" name="new_folder" id="new_folder" value="" size="30" />
            </div>
        </div>
        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" cancel_action="close" but_meta="cm-te-create-folder cm-dialog-closer"}
        </div>
        </form>
    {/capture}

    {capture name="add_new_file"}
        <form name="add_file_form" class="form-horizontal cm-form-highlight form-edit">
        <div class="control-group">
            <label for="new_file" class="control-label cm-required">{__("name")}:</label>
            <div class="controls">
                <input type="text" class="span9" name="new_file" id="new_file" value="" size="30" />
            </div>
        </div>
        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" cancel_action="close" but_meta="cm-dialog-closer cm-te-create-file"}
        </div>
        </form>
    {/capture}   
</div>

{if !"IS_WINDOWS"|defined}
    <div class="hidden" title="{__("change_permissions")}" id="content_chmod">
        {include file="views/template_editor/components/chmod.tpl"}
    <!--content_{$id}--></div>
{/if}

{capture name="buttons"}
    {capture name="tools_list"}
        {$current_url = $config.current_url|escape:"url"}
        <li class="cm-te-onsite-editing">{btn type="list" text=__("on_site_template_editing") href="customization.update_mode?type=design&status=enable&return_url=`$current_url`"|fn_url target="_blank"}</li>
        <li class="divider"></li>
        <li class="cm-te-restore">{btn type="list" text=__("restore_from_repository") }</li>
        {if !"IS_WINDOWS"|defined}
            <li>{include file="common/popupbox.tpl" id="chmod" link_text=__("change_permissions") act="link" link_text=__("change_permissions") link_class="cm-te-perms"}</li>
        {/if}
        <li class="cm-te-getfile">{btn type="list" text=__("download")}</li>
        <li class="cm-te-rename">{btn type="list" text=__("rename") }</li>
        <li class="cm-te-delete">{btn type="list" text=__("delete") }</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list class="ce-te-actions"}
    {include file="buttons/save_changes.tpl" but_meta="cm-te-save-file btn-primary disabled" but_role="submit" but_onclick="fn_save_template();"}
    
{/capture}

{capture name="sidebar"}
    <div class="sidebar-row">
        <a class="collapsed" data-toggle="collapse" data-target="#acc_debugging_modes">{__("development_settings")}
            <b class="exicon-collapse"></b>
        </a>

        <div class="collapse" id="acc_debugging_modes">
            <ul class="unstyled list-with-btns">
                <li>
                    <div class="list-description">
                        {__("rebuild_cache_automatically")} <i class="cm-tooltip icon-question-sign" title="{__("rebuild_cache_automatically_tooltip")}"></i>
                    </div>
                    <div class="switch switch-mini cm-switch-change list-btns" id="rebuild_cache_automatically">
                        <input type="checkbox" name="compile_check" value="1" {if $dev_modes.compile_check}checked="checked"{/if}/>
                    </div>
                </li>
                <li>
                    <div class="list-description">
                        {__("dev_css")} <i class="cm-tooltip icon-question-sign" title="{__("dev_css_tooltip")}"></i>
                    </div>
                    <div class="switch switch-mini cm-switch-change list-btns" id="elm_compile_css">
                        <input type="checkbox" name="dev_css" value="1" {if $dev_modes.dev_css}checked="checked"{/if} />
                    </div>
                </li>
                <li>
                    <div class="list-description">
                        {__("dev_js")} <i class="cm-tooltip icon-question-sign" title="{__("dev_js_tooltip")}"></i>
                    </div>
                    <div class="switch switch-mini cm-switch-change list-btns§" id="elm_compile_js">
                        <input type="checkbox" name="dev_js" value="1" {if $dev_modes.dev_js}checked="checked"{/if}/>
                    </div>
                </li>
            </ul>

        </div>
    </div>
    <script type="text/javascript">
        //<![CDATA[
        (function (_, $) {
            $(_.doc).on('switch-change', '.cm-switch-change', function (e, data) {

                var value = data.value;
                $.ceAjax('request', fn_url("template_editor.update_dev_mode"), {
                    data: {
                        dev_mode: data.el.prop('name'),
                        state: value ? 1 : 0
                    }
                });
            });
        }(Tygh, Tygh.$));
        //]]>
    </script>

    <hr>
    <div class="sidebar-row te-file-wrapper">
        <h6>{__("files")}</h6>
        <!--file tree-->
        <div id="filelist" class="cm-te-file-tree nested-list nested-list-folders"></div>
        <!--#file tree-->
    </div>
{/capture}

{capture name="adv_buttons"}
    {capture name="tools_list"}
        <li class="cm-te-create-file">{include file="common/popupbox.tpl" id="add_new_file" text=__("new_file") content=$smarty.capture.add_new_file link_text=__("create_file") act="edit" no_icon_link="true"}</li>
        <li class="cm-te-create-folder">{include file="common/popupbox.tpl" id="add_new_folder" text=__("new_folder") content=$smarty.capture.add_new_folder link_text=__("create_folder") act="edit" no_icon_link="true"}</li>
        <li>{include file="common/popupbox.tpl" id="upload_file" text=__("upload_file") content=$smarty.capture.upload_file link_text=__("upload_file") act="edit" no_icon_link="true"}</li>
    {/capture}
    {include file="common/tools.tpl" prefix="main" tool_meta="cm-te-create" hide_actions=true tools_list=$smarty.capture.tools_list display="inline" title=__("create") icon="icon-plus"}
{/capture}

{capture name="mainbox_title"}
{__("template_editor")}<span class="muted f-small cm-te-path te-path"></span>
{/capture}

{*include file="views/template_editor/components/template_editor_picker.tpl"*}

{/capture}
{include file="common/mainbox.tpl" content=$smarty.capture.mainbox title=$smarty.capture.mainbox_title buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
