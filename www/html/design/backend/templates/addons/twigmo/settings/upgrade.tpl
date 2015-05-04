<div id="addon_upgrade">

{include file="addons/twigmo/settings/contact_twigmo_support.tpl"}

{include file="common/subheader.tpl" title=__('upgrade')}

{if $next_version_info.next_version and $next_version_info.next_version != $smarty.const.TWIGMO_VERSION}
    <p>{$next_version_info.description nofilter}</p>

    <input type="submit" name="dispatch[upgrade_center.upgrade_twigmo]" value="{__('upgrade')}" class="cm-skip-validation btn btn-primary">
{else}
    <p>{__('text_no_upgrades_available')}</p>
    <div class="buttons-container">
        {include file="buttons/button.tpl" but_name="dispatch[twigmo_updates.check]" but_text=__('twgadmin_check_for_updates') but_role="submit" but_meta="cm-ajax cm-skip-avail-switch"}
        {if $addons.twigmo.access_id}
            <input type="hidden" name="result_ids" value="addon_upgrade" />
        {/if}
    </div>
{/if}
<!--addon_upgrade--></div>
