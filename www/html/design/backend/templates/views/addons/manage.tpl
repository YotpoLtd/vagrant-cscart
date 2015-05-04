{include file="views/profiles/components/profiles_scripts.tpl" states=1|fn_get_all_states}

{script src="js/tygh/tabs.js"}
{script src="js/tygh/fileuploader_scripts.js"}

{capture name="mainbox"}

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="addons.manage" view_type="addons"}
    {include file="views/addons/components/addons_search_form.tpl" dispatch="addons.manage"}
{/capture}

{assign var="c_url" value=$config.current_url|escape:url}

<div class="items-container" id="addons_list">
{hook name="addons:manage"}
{if $addons_list}
    <table class="table table-addons">
{foreach from=$addons_list item="a" key="key"}

    {assign var="non_editable" value=false}
    {assign var="href_delete" value=""}
    {assign var="href" value="addons.update?addon=`$a.addon`&return_url=$c_url"|fn_url}
    {assign var="display" value="text"}

    {if $runtime.company_id}
        {assign var="hide_for_vendor" value=true}
    {/if}

    {if $a.status == "N"}
        {assign var="non_editable" value=true}
    {else}
        {assign var="display" value="popup"}
        {if $a.has_options}
            {assign var="act" value="edit"}
        {else}
            {assign var="act" value="none"}
            {assign var="non_editable" value=true}
        {/if}
        {if !$hide_for_vendor} 
            {assign var="href_delete" value="addons.uninstall?addon=`$a.addon`&redirect_url=$c_url"|fn_url}
        {/if}
    {/if}

    {if $a.separate && !$non_editable}
        {assign var="link_text" value=__("manage")}
    {elseif $a.status != "N"}
        {assign var="link_text" value=__("settings")}
    {else}
        {assign var="link_text" value="&nbsp;"}
    {/if}

        <tr class="cm-row-status-{$a.status|lower} {$additional_class} cm-row-item" id="addon_{$key}">
            <td class="addon-icon">
                <div class="bg-icon">
                    {*
                    {if $a.has_icon}
                        <img src="{$images_dir}/addons/{$key}/icon.png" width="38" height="38" border="0" alt="{$a.name}" title="{$a.name}"/>
                    {/if}
                    *}
                    {if $a.status == "N"}
                        <i class="exicon-box"></i>
                    {else}
                        <i class="exicon-box-blue"></i>
                    {/if}
                </div>
            </td>
            <td width="80%">
                <div class="object-group-link-wrap">
                {if !$non_editable}
                    {if $a.separate}
                    <a href="{$href}">{$a.name}</a>
                    {else}
                    <a class="row-status cm-external-click{if $non_editable} no-underline{/if} {if !$a.snapshot_correct}cm-promo-popup{/if}" {if $a.snapshot_correct}data-ca-external-click-id="opener_group{$key}"{/if}>{$a.name}</a>
                    {/if}
                {else}
                    <span class="unedited-element block">{$a.name|default:__("view")}</span>
                {/if}
                    <br><span class="row-status object-group-details">{$a.description nofilter}</span>
                </div>
            </td>
            <td width="10%" class="right nowrap">

                {if $a.status != 'N'}
                <div class="pull-right">
                {capture name="tools_list"}
                    {if $a.separate}
                        {if !$non_editable}
                            <li>{btn type="list" text=$link_text href=$href}</li>
                        {else}
                            <li class="disabled"><a>{$link_text}</a></li>
                        {/if}
                    {else}
                        <li>{include file="common/popupbox.tpl" id="group`$key`" text="{__("settings")}: `$a.name`" act=$act|default:"link" link_text=$link_text href=$href is_promo=!$a.snapshot_correct}</li>
                    {/if}
                    {if $href_delete}
                        <li>{btn type="list" class="cm-confirm" text=__("uninstall") data=['data-ca-target-id'=>'addons_list,header_navbar,header_subnav'] href=$href_delete}</li>
                    {/if}
                {/capture}
                {dropdown content=$smarty.capture.tools_list}
                </div>
                {/if}

            </td>
            <td width="15%">
                {if $a.status == 'N'}
                    {if !$hide_for_vendor}
                    <div class="pull-right">
                        <a class="btn lowercase {if $a.snapshot_correct}cm-ajax cm-ajax-full-render{else}cm-promo-popup{/if}" href="{"addons.install?addon=`$key`&redirect_url=$c_url"|fn_url}" data-ca-target-id="addons_list,header_navbar,header_subnav">{__("install")}</a>
                    </div>
                    {/if}
                {else}
                    <div class="pull-right nowrap">
                        {if !$a.snapshot_correct}{$status_meta = "show-promo-popup"}{else}{$status_meta = ""}{/if}
                        {include file="common/select_popup.tpl" popup_additional_class="dropleft" id=$key status=$a.status hide_for_vendor=$hide_for_vendor non_editable=false status_meta=$status_meta display=$display update_controller="addons"}
                    </div>
                {/if}
            </td>
        <!--addon_{$key}--></tr>
{/foreach}
</table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
{/hook}
<!--addons_list--></div>

{/capture}
{include file="common/mainbox.tpl" title=__("addons") content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar}