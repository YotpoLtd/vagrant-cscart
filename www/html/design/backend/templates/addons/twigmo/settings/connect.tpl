{include file="addons/twigmo/settings/contact_twigmo_support.tpl"}

{if $addons.twigmo.access_id}
    {include file="common/subheader.tpl" title=__("twgadmin_manage_settings")}
{else}
    {include file="common/subheader.tpl" title=__("twgadmin_connect_your_store")}
{/if}

<fieldset>

<div id="connect_settings">

    {if $user_info.email != $smarty.const.DEFAULT_ADMIN_EMAIL}
        {assign var="tw_email" value=$addons.twigmo.email|default:$user_info.email}
    {else}
        {assign var="tw_email" value=$addons.twigmo.email|default:""}
    {/if}


    <div class="control-group">
        <label class="control-label{if !$addons.twigmo.access_id} cm-required cm-email{/if}" for="elm_tw_email">{__("email")}:</label>
        <div class="controls">
            <input type="text" id="elm_tw_email" name="tw_register[email]"  value="{$tw_email}" onkeyup="fn_tw_copy_email();" size="60" {if $addons.twigmo.access_id}disabled="disabled"{/if} />
            {if !$addons.twigmo.access_id}
                {include file="buttons/button.tpl" but_text=__("reset_password") but_href="http://twigmo.com/svc/reset_password.php?email=$tw_email" but_id="elm_reset_tw_password" but_role="link"}
            {/if}
        </div>
    </div>

    {if $addons.twigmo.access_id}
    <div class="control-group">
        <label class="control-label" for="access_id">{__("twgadmin_access_id")}:</label>
        <div class="controls" id="access_id">
            <div class="text-type-value">{$addons.twigmo.access_id}</div>
        </div>
    </div>
    {/if}

    <input type="hidden" id="elm_tw_store_name" name="tw_register[store_name]"  value="{if $addons.twigmo.store_name}{$addons.twigmo.store_name}{else}{$config.http_host}{$config.http_path}{/if}"/>

    {if !$addons.twigmo.access_id}
        <div id='twg_passwords'>
            <div class="control-group">
                <label for="elm_tw_password1" class="control-label {if !$addons.twigmo.access_id} cm-required{/if}">{__("password")}:</label>
                <div class="controls">
                    <input type="password" id="elm_tw_password1" name="tw_register[password1]" size="32" maxlength="32" value="" autocomplete="off">
                </div>
            </div>
            <div class="control-group">
                <label for="elm_tw_password2" class="control-label {if !$addons.twigmo.access_id} cm-required{/if}">{__("confirm_password")}:</label>
                <div class="controls">
                    <input type="password" id="elm_tw_password2" name="tw_register[password2]" size="32" maxlength="32" value="" autocomplete="off">
                </div>
            </div>
        </div>
    {/if}

    <div class="control-group">
        <label for="version" class="control-label">{__("version")}:</label>
        <div class="controls" id="version">
            <div class="text-type-value">{$addons.twigmo.version|default:$tw_register.version}</div>
        </div>
    </div>

    {* Social buttons *}
    {if $addons.twigmo.access_id}
        <div class="control-group">
            <label for="social_links" class="control-label">{__("twgadmin_on_social")}:</label>
            <div id="social_links" class="controls">
                {* Facebook *}
                <a target="_blank" href="http{if 'HTTPS'|defined}s{/if}://facebook.com/twigmo">
                    <span class="facebook-btn"><img src="{$images_dir}/addons/twigmo/images/buttons/facebook.png"></span>
                </a>
                {* /Facebook *}
                
                {* Twitter *}
                <a target="_blank" href="http{if 'HTTPS'|defined}s{/if}://twitter.com/twigmo">
                    <span class="twitter-btn"><img src="{$images_dir}/addons/twigmo/images/buttons/twitter.png"></span>
                </a>
                {* /Twitter *}
            </div>
        </div>
    {/if}
    {* /Social buttons *}

    {if !$addons.twigmo.access_id}
        <input type="hidden" name="result_ids" value="connect_settings,storefront_settings,addon_upgrade"/>
        <input type="hidden" name="tw_register[checked_email]" value="{$addons.twigmo.checked_email}"/>

        <div class="control-group">
            <div class="controls">
                <textarea id="twigmo_license" name="tw_register[twigmo_license]" cols="23" rows="12" class="input-large" readonly="readonly" disabled="disabled">{if $twigmo_license}{$twigmo_license}{/if}</textarea>
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                <label for="id_accept_terms" class="cm-check-agreement checkbox">
                    <input type="checkbox" id="id_accept_terms" name="tw_register[accept_terms]" value="Y">
                    {__("checkout_terms_n_conditions")}
                </label>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                {include file="buttons/button.tpl" but_role="submit" but_meta="btn-primary cm-ajax cm-skip-avail-switch" but_name="dispatch[addons.tw_connect]" but_text=__("twgadmin_connect")}
            </div>
        </div>
    {/if}

<!--connect_settings--></div>

</fieldset>
