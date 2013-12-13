
{assign var="m_url" value=$smarty.request.manage_url|escape:"url"}

{script src="js/tygh/block_manager.js"}

<script type="text/javascript">
    var selected_location = '{$location.location_id|default:0}';

    var dynamic_object_id = '{$dynamic_object.object_id|default:0}';
    var dynamic_object_type = '{$dynamic_object_scheme.object_type|default:''}';

    var BlockManager = new BlockManager_Class();

{literal}
    if (dynamic_object_id > 0) {
        var items = null;
    } else {
        var items = '.block';
    }

    (function(_, $) {
        $(document).ready(function() {
            $('#content_location_' + selected_location).appear(function(){
                BlockManager.init('.grid', {
                    // UI settings
                    connectWith: '.grid',
                    items: items,
                    revert: true,
                    placeholder: 'ui-hover-block',
                    opacity: 0.5,
                    
                    // BlockManager_Class settings
                    container_class: 'container',
                    grid_class: 'grid',
                    block_class: 'block',
                    hover_element_class: 'hover-element'
                });
            });
        });
    }(Tygh, Tygh.$));
{/literal}
</script>

{if $dynamic_object.object_id > 0}
    {style src="block_manager_in_tab.css"}
{/if}
{style src="lib/960/960.css"}

<div id="block_window" class="grid-block hidden"></div>
<div id="block_manager_menu" class="grid-menu hidden"></div>
<div id="block_manager_prop" class="grid-prop hidden"></div>

{include file="views/block_manager/render/grid.tpl" default_class="base-grid hidden" show_menu=true}
{include file="views/block_manager/render/block.tpl" default_class="base-block hidden" block_data=true}

{capture name="mainbox"}
{capture name="tabsbox"}
    <div id="content_location_{$location.location_id}">
        {render_location
            dispatch=$location.dispatch
            location_id=$location.location_id
            area='A'
            lang_code=$location.lang_code
        }
    </div>
{/capture}

{capture name="adv_buttons"}
    {capture name="add_new_picker"}
        {include file="views/block_manager/components/update_layout.tpl" layout_data=[]}
    {/capture}
    {include file="common/popupbox.tpl" id="add_new_layout" text=__("new_layout") content=$smarty.capture.add_new_picker act="general" icon="icon-plus" title=__("add_layout")}
{/capture}

{capture name="buttons"}
    {* Display this buttons only on block manager page *}
    {if !$dynamic_object.object_id}
        {capture name="tools_list"}
            <li>
                {include file="common/popupbox.tpl"
                id="manage_blocks"
                text=__("block_manager")
                link_text=__("manage_blocks")
                link_class="cm-action bm-action-manage-blocks"
                act="link"
                content=""
                general_class="action-btn"}
            </li>
            <li class="divider"></li>
            <li>
                {include file="common/popupbox.tpl"
                id="export_locations_manager"
                text=__("export_locations")
                link_text=__("export_locations")
                act="link"
                href="block_manager.export_locations"
                opener_ajax_class="cm-ajax"
                link_class="cm-ajax-force"
                content=""
                general_class="action-btn"}
            </li>
            <li>
                {include file="common/popupbox.tpl"
                id="import_locations_manager"
                text=__("import_locations")
                link_text=__("import_locations")
                act="link"
                href="block_manager.import_locations"
                opener_ajax_class="cm-ajax"
                link_class="cm-ajax-force"
                content=""
                general_class="action-btn"
            }
            </li>
            <li class="divider"></li>
            <li>
                {capture name="add_new_picker"}
                    {include file="views/block_manager/components/update_layout.tpl" layout_data=$layout_data}
                {/capture}
                {include file="common/popupbox.tpl" id="upate_layout" text="{__("editing_layout")}: `$layout_data.name`" content=$smarty.capture.add_new_picker act="link" link_text=__("edit_layout")}
            </li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{script src="js/tygh/tabs.js"}

<div class="cm-j-tabs tabs tabs-with-conf">
    <ul class="nav nav-tabs">
        {foreach from=$navigation.tabs item=tab key=key name=tabs}
                <li id="{$key}{$id_suffix}" class="{if $tab.hidden == "Y"}hidden {/if}{if $key == "location_`$location.location_id`"}active extra-tab{/if}">
                    {if $key == "location_`$location.location_id`"}
                        {btn type="dialog" class="cm-ajax-force hand icon-cog" href="block_manager.update_location?location=`$location.location_id`" id="tab_location_`$location.location_id`" title="{__("editing_location")}: `$tab.title`"}
                    {/if}
                    <a {if $tab.href}href="{$tab.href|fn_url}"{/if}>{$tab.title}</a>
                </li>
        {/foreach}
        <li class="divider"></li>
        {if !$dynamic_object.object_id}
            <li class="cm-no-highlight">
                {include file="common/popupbox.tpl"
                id="add_new_location"
                text=__("new_location")
                link_text="{__("add_location")}…"
                act="link"
                href="block_manager.update_location"
                opener_ajax_class="cm-ajax"
                link_class="cm-ajax-force"
                icon="icon-plus"
                content=""}</li>
        {/if}
    </ul>
</div>
<div class="cm-tabs-content">
    {$smarty.capture.tabsbox nofilter}
</div>

{/capture}

{capture name="sidebar"}
    <div id="block_manager_sidebar">
        {if $layouts|count > 1}
        <div class="sidebar-row layouts">
            <h6>{__("switch_layout")}</h6>
            <div class="clearfix">
                {include file="common/select_object.tpl" style="graphic" link_tpl=$config.current_url|fn_link_attach:"s_layout=" items=$layouts selected_id=$runtime.layout.layout_id key_name="name" display_icons=false}
            </div>
        </div>
        <hr>
        {/if}

        {hook name="layouts:sidebar"}
        <div class="sidebar-row">
{capture name="widget_code"}
<div class="tygh" id="tygh_container">
<script type="text/javascript" src="http://widget.cart-services.com/init.js?url={"http://`$runtime.company_data.storefront`/"|escape:url}&amp;layout={$runtime.layout.layout_id}"></script>
</div>
{/capture}
            <h6>{__("widget_code")}</h6>
            <div class="clearfix">
                    <fieldset>
                        <textarea class="copy-field" id="widget_code_box" readonly="readonly" cols="30" rows="10">{$smarty.capture.widget_code}</textarea>
                        <a class="pull-right cm-select-text" data-ca-select-id="widget_code_box">{__("select_all")}</a>
                    </fieldset>
            </div>

        </div>
        {/hook}

    <!--block_manager_sidebar--></div>
{/capture}

{if $dynamic_object.object_id}
    {$smarty.capture.mainbox nofilter}
{else}
    {include file="common/mainbox.tpl" title="{__("editing_layout")}: `$layout_data.name`" adv_buttons=$smarty.capture.adv_buttons buttons=$smarty.capture.buttons content=$smarty.capture.mainbox select_languages=true sidebar=$smarty.capture.sidebar}
{/if}