{if "THEMES_PANEL"|defined}
    {$sticky_scroll = 5}
    {$sticky_padding = 73}
{else}
    {$sticky_scroll = 41}
    {$sticky_padding = 37}
{/if}

{if $anchor}
<a name="{$anchor}"></a>
{/if}

<script type="text/javascript">
//<![CDATA[
// Init ajax callback (rebuild)
var menu_content = {$data|unescape|default:"''" nofilter};

//]]>
</script>

<!-- Actions -->
<div class="actions cm-sticky-scroll" id="actions_panel" data-ce-top="{$sticky_scroll}" data-ce-padding="{$sticky_padding}">
    {if !$no_sidebar}
        <div class="btn-bar-left pull-left">
            <div class="pull-left">{include file="common/last_viewed_items.tpl"}</div>
            <div class="pull-right adv-buttons" {if $content_id}id="tools_{$content_id}_adv_buttons"{/if}>
                {$adv_buttons nofilter}
            {if $content_id}<!--tools_{$content_id}_adv_buttons-->{/if}</div>
        </div>
    {/if}
    <div class="title pull-left">
        <h2 title="{$title_alt|default:$title|strip_tags|strip}">{$title|default:"&nbsp;" nofilter}</h2>
    </div>
    <div class="{if isset($main_buttons_meta)}{$main_buttons_meta}{else}btn-bar btn-toolbar{/if} dropleft pull-right" {if $content_id}id="tools_{$content_id}_buttons"{/if}>
        {if $navigation.dynamic.actions}
            {capture name="tools_list"}
                {foreach from=$navigation.dynamic.actions key=title item=m name="actions"}
                    <li><a href="{$m.href|fn_url}" class="{$m.meta}" target="{$m.target}">{__($title)}</a></li>
                {/foreach}
            {/capture}
            {include file="common/tools.tpl" hide_actions=true tools_list=$smarty.capture.tools_list link_text=__("choose_action")}
        {/if}

        {$buttons nofilter}
    {if $content_id}<!--tools_{$content_id}_buttons-->{/if}</div>
</div>

<!-- Sidebar -->
{if !$no_sidebar}
<div class="sidebar" id="elm_sidebar">
    <div class="sidebar-wrapper">
        {if $select_languages && $languages|sizeof > 1}
            <div class="sidebar-row">
                {if !"ULTIMATE:FREE"|fn_allowed_for}
                    {include file="common/select_object.tpl" style="graphic" link_tpl=$config.current_url|fn_link_attach:"descr_sl=" items=$languages selected_id=$smarty.const.DESCR_SL key_name="name" suffix="content" display_icons=true}
                {/if}
            </div>
            <hr />
        {/if}

        {if $navigation && $navigation.dynamic.sections}
            <div class="sidebar-row">
                <ul class="nav nav-list">
                    {foreach from=$navigation.dynamic.sections item=m key="s_id" name="first_level"}
                        {hook name="index:dynamic_menu_item"}
                            {if $m.type == "divider"}
                                <li class="divider"></li>
                                {else}
                                <li class="{if $m.js == true}cm-js{/if}{if $smarty.foreach.first_level.last} last-item{/if}{if $navigation.dynamic.active_section == $s_id} active{/if}"><a href="{$m.href|fn_url}">{$m.title}</a></li>
                            {/if}
                        {/hook}
                    {/foreach}
                </ul>
            </div>
        <hr>
        {/if}
        {$sidebar nofilter}

        {notes assign="notes"}{/notes}
        {if $notes}
            {foreach from=$notes item="note" key="title"}
                {capture name="note_title"}
                    {if $title == "_note_"}{__("notes")}{else}{$title}{/if}
                {/capture}
                {include file="common/sidebox.tpl" content=$note title=$smarty.capture.note_title}
            {/foreach}
        {/if}
    </div>
<!--elm_sidebar--></div>
{/if}

{* DO NOT REMOVE HTML comment below *}
<!--Content-->
<div class="content{if $no_sidebar} content-no-sidebar{/if} {if "ULTIMATE"|fn_allowed_for}ufa{/if}" {if $box_id}id="{$box_id}"{/if}>
    <div class="content-wrap">
    
    {if $tools}{$tools nofilter}{/if}

    {if $title_extra}<div class="title">-&nbsp;</div>
        {$title_extra nofilter}
    {/if}

    {if $extra_tools|trim}
        <div class="extra-tools">
            {$extra_tools nofilter}
        </div>
    {/if}

    {if $content_id}<div id="content_{$content_id}">{/if}
        {$content|default:"&nbsp;" nofilter}
    {if $content_id}<!--content_{$content_id}--></div>{/if}

    {if $box_id}<!--{$box_id}-->{/if}</div>
</div>
{* DO NOT REMOVE HTML comment below *}
<!--/Content-->

<script type="text/javascript">
    var ajax_callback_data = menu_content;
</script>
