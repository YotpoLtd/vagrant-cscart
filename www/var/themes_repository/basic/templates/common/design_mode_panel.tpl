<div id="design_mode_panel" class="popup {if $runtime.customization_mode.design}customization{else}translate{/if}-mode" style="{if $smarty.cookies.design_mode_panel_offset}{$smarty.cookies.design_mode_panel_offset}{/if}">
    <div>
        <h1>{if $runtime.customization_mode.design}{__("customization_mode")}{else}{__("translate_mode")}{/if}</h1>
    </div>
    <div>
        <form action="{""|fn_url}" method="post" name="design_mode_panel_form">
            <input type="hidden" name="current_url" value="{$config.current_url}" />
            <input type="submit" name="dispatch[design_mode.update_customization_mode]" value="" class="hidden" />
            {if $runtime.customization_mode.design}
                <input type="hidden" name="customization_modes[design]" value="disable" />
                <input type="hidden" name="customization_modes[translation]" value="enable" />
                {assign var="mode_val" value=__("switch_to_translation_mode")}
            {else}
                <input type="hidden" name="customization_modes[design]" value="enable" />
                <input type="hidden" name="customization_modes[translation]" value="disable" />            
                {assign var="mode_val" value=__("switch_to_customization_mode")}
            {/if}
            <p class="right"><a class="cm-submit" data-ca-dispatch="dispatch[design_mode.update_customization_mode]">{$mode_val}</a></p>
        </form>
    </div>
</div>