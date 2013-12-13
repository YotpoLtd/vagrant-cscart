{include file="common/previewer.tpl"}

{capture name="mainbox"}

<div class="themes" id="themes_list">

{capture name="tabsbox"}

<div id="content_general">
    {$theme = $available_themes.current}
    {$theme_name = $available_themes.current.theme_name}

    <div id="themes_manage" class="themes-current clearfix">
        <div class="row">

            {capture name="add_new_picker"}
                <form action="{""|fn_url}" method="post" name="clone_theme_{$theme_name}_form" class="cm-ajax cm-comet cm-form-dialog-closer form-horizontal form-edit ">
                    <input type="hidden" name="theme_data[theme_src]" value="{$theme_name}">
                    <input type="hidden" name="result_ids" value="themes_list,elm_sidebar">

                    <div class="add-new-object-group">
                        <div class="tabs cm-j-tabs">
                            <ul class="nav nav-tabs">
                                <li id="tab_clone_theme_{$theme_name}" class="cm-js active"><a>{__("general")}</a></li>
                            </ul>
                        </div>

                        <div class="cm-tabs-content" id="content_tab_clone_theme_{$theme_name}">
                            <fieldset>
                                <div class="control-group">
                                    <label class="control-label cm-required" for="elm_theme_dir_{$theme_name}">{__("directory")}</label>
                                    <div class="controls">
                                        <input type="text" id="elm_theme_dir_{$theme_name}" name="theme_data[theme_dest]" value="{$theme_name}_clone" />
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label class="control-label" for="elm_theme_title_{$theme_name}">{__("name")}</label>
                                    <div class="controls">
                                        <input type="text" id="elm_theme_title_{$theme_name}" name="theme_data[title]" value="{$theme.title}" />
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label class="control-label" for="elm_theme_desc_{$theme_name}">{__("description")}</label>
                                    <div class="controls">
                                        <textarea name="theme_data[description]" id="elm_theme_desc_{$theme_name}" cols="50" rows="4" class="span9">{$theme.description}</textarea>
                                    </div>
                                </div>

                            </fieldset>
                        </div>
                    </div>

                    <div class="buttons-container">
                        {include file="buttons/save_cancel.tpl" but_name="dispatch[themes.clone]" cancel_action="close" save=true}
                    </div>

                </form>
            {/capture}

        {if $theme.screenshot}
            <img class="span4 screenshot" width="250" src="{$theme.screenshot}">
        {/if}
        <div class="span8">
            <h4>{$theme.title}</h4>
            <span class="muted">{__("current_theme")}</span>
            <p>{$theme.description}</p>

            {hook name="themes:current_theme_options"}
            <div class="pull-left">
                {include file="buttons/button.tpl" but_href="customization.update_mode?type=theme_editor&status=enable" but_text=__("customize_theme") but_role="action" but_meta="btn-primary" but_target="_blank"}
                {include file="common/popupbox.tpl" id="elm_clone_theme_`$theme_name`" text=__("clone_theme") content=$smarty.capture.add_new_picker act="general" link_text=__("clone_theme")}
            </div>

            <div class="muted pull-left themes-options">
                <span>{__("options")}:</span> <a href="{"block_manager.manage"|fn_url}">{__("layouts")}</a> | <a href="{"template_editor.manage"|fn_url}">{__("template_editor")}</a>
            </div>
            {/hook}
        </div>
        </div>
    </div>

    <div class="themes-available">
    <h4>{__("available_themes")}</h4>

    {if $available_themes.installed}

    {split data=$available_themes.installed size=3 assign="splitted_themes" simple=true}

    {foreach from=$splitted_themes item="repo_themes"}
    <div class="row">
    {foreach from=$repo_themes item="theme" key="theme_name"}
        {if $theme}
            <div class="span4">
                <div class="theme">

                    {if $theme.screenshot}
                    <a id="image_img_{$theme_name}" href="{$theme.screenshot}" data-ca-image-id="img_{$theme_name}" class="cm-previewer"><img class="screenshot" src="{$theme.screenshot}" alt="" width="250"></a>
                    {/if}
                    <div class="theme-actions">
                        {capture name="tools_list"}
                            {hook name="themes:available_theme_options"}
                            <li><a href="{"themes.set?theme_name=`$theme_name`"|fn_url}">{__("select")}</a></li>
                            <li><a class="cm-confirm" href="{"themes.delete?theme_name=`$theme_name`"|fn_url}">{__("remove")}</a></li>
                            {/hook}
                        {/capture}
                        {dropdown content=$smarty.capture.tools_list placement="right"}
                        <div class="theme-title pull-right">
                            <strong title="{$theme.title}">{$theme.title}</strong>
                            <p class="muted" title="/{$theme_name}">/{$theme_name}</p>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
    {/foreach}
    <!--row--></div>
    {/foreach}
    {else}
        <div class="no-items">
            {__("no_themes_available")}
        </div>
    {/if}
    </div>
</div>
<div id="content_install_themes">

{hook name="themes:install_themes"}
    {if $available_themes.repo}

    {split data=$available_themes.repo size=3 assign="splitted_themes" simple=true}
    <div class="themes-available">
    {foreach from=$splitted_themes item="repo_themes"}
    <div class="row">
    {foreach from=$repo_themes item="theme" key="theme_name"}
        {if $theme}
            <div class="span4">
                <div class="theme">

                    {if $theme.screenshot}
                    <a id="image_img_{$theme_name}" href="{$theme.screenshot}" data-ca-image-id="img_{$theme_name}" class="cm-previewer"><img class="screenshot" src="{$theme.screenshot}" alt="" width="250"></a>
                    {/if}

                    <div class="theme-actions">
                        {capture name="tools_list"}

                            {if $theme.screenshot}
                            <li><a id="image_img_{$theme_name}" href="{$theme.screenshot}" data-ca-image-id="img_{$theme_name}" class="cm-previewer">{__("preview")}</a></li>
                            {/if}

                            {* <li><a href={$config.demo_store_url}?demo_theme[C]={$theme_name}>{__("live_preview")}</a></li> *}
                            <li><a class="cm-comet cm-ajax" data-ca-target-id="themes_list" href="{"themes.install?theme_name=`$theme_name`"|fn_url}">{__("install")}</a></li>
                        {/capture}
                        {dropdown content=$smarty.capture.tools_list placement="right"}
                        <div class="theme-title pull-right">
                            <strong title="{$theme.title}">{$theme.title}</strong>
                            <p class="muted" title="/{$theme_name}">/{$theme_name}</p>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
    {/foreach}
    </div>
    {/foreach}
    {else}
        <div class="no-items">
            {__("no_themes_available")}
        </div>
    {/if}
    </div>
{/hook}

</div>

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section}
<!--themes_list--></div>

{capture name="sidebar"}
    <div class="container themes-side">
        {hook name="themes:options"}
        <div class="sidebar-row clearfix">
            <h6>{__("active_preset")}</h6>
            <div class="row-fluid">
                <div class="span7 muted" title="{$layout.preset_name}">{$layout.preset_name}</div>
                <div class="span5 right"><a href="{"customization.update_mode?type=theme_editor&status=enable"|fn_url}" target="_blank">{__("customize")}</a></div>
            </div>
        </div>
        <hr>
        <div class="sidebar-row clearfix">
            <h6>{__("default_layout")}</h6>
            <div class="row-fluid">
                <div class="span7 muted" title="{$layout.name}">{$layout.name}</div>
                <div class="span5 right"><a class="pull-right" href="{"block_manager.manage"|fn_url}">{__("configure")}</a></div>
            </div>
        </div>
        <hr>
        <div class="sidebar-row clearfix">
            <h6>{__("theme_directory")}</h6>
            <div class="row-fluid">
                <div class="span7 muted" title="/{$settings.theme_name}">/{$settings.theme_name}</div>
                <div class="span5 right"><a class="pull-right" href="{"template_editor.manage"|fn_url}">{__("edit_files")}</a></div>
            </div>
        </div>
        {/hook}
    </div>
{/capture}

{/capture}
{include file="common/mainbox.tpl" title={__("themes")} content=$smarty.capture.mainbox select_languages=true sidebar=$smarty.capture.sidebar}
