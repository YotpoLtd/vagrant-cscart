<div id="storefront_settings">

{include file="addons/twigmo/settings/contact_twigmo_support.tpl"}

{include file="common/subheader.tpl" title=__('twgadmin_manage_storefront_settings')}

{if !$addons.twigmo.access_id}
    {if !("ULTIMATE"|fn_allowed_for) || !$runtime.company_id}
        {__("twgadmin_connect_to_first")}
    {else}
        {__("twgadmin_connect_to_first_ult")}
    {/if}
{elseif "ULTIMATE"|fn_allowed_for && !$runtime.company_id}
    {__("twgadmin_select_store")} - {include file="common/ajax_select_object.tpl" data_url="companies.get_companies_list?show_all=Y&action=href"  text=__("select") id=$select_id|default:"twg_top_company_id"}
{else}

<input type="hidden" name="result_ids" value="connect_settings,storefront_settings,addon_upgrade" />

<fieldset>
    {* Use mobile frontend for ... *}
    <div class="control-group form-field">
        <label class="control-label" for="elm_tw_use_mobile_frontend">{__("twgadmin_use_mobile_frontend")}:</label>
        <div  class="controls">
            <select id="elm_tw_use_mobile_frontend" name="tw_settings[use_mobile_frontend]">
                <option    value="never" {if $addons.twigmo.use_mobile_frontend == "never"}selected="selected"{/if}>{__("twgadmin_never")}</option>
                <option    value="phone" {if $addons.twigmo.use_mobile_frontend == "phone"}selected="selected"{/if}>{__("twgadmin_phone")}</option>
                <option    value="tablet" {if $addons.twigmo.use_mobile_frontend == "tablet"}selected="selected"{/if}>{__("twgadmin_tablet")}</option>
                <option    value="both_tablet_and_phone" {if $addons.twigmo.use_mobile_frontend == "both_tablet_and_phone"  || !$addons.twigmo.use_mobile_frontend}selected="selected"{/if}>{__("twgadmin_both_tablet_and_phone")}</option>
            </select>
        </div>
    </div>

    {* Home page content *}
    <div class="form-field control-group">
        <label class="control-label" for="elm_tw_home_page_content">{__("twgadmin_home_page_content")}:</label>
        <div  class="controls">
                <select id="elm_tw_home_page_content" name="tw_settings[home_page_content]">
                        <option    value="home_page_blocks" {if $addons.twigmo.home_page_content == "home_page_blocks"}selected="selected"{/if}>- {__("twgadmin_home_page_blocks")} -</option>
                        <option    value="tw_home_page_blocks" {if $addons.twigmo.home_page_content == "tw_home_page_blocks"}selected="selected"{/if}>- {__("twgadmin_tw_home_page_blocks")} -</option>
                        {foreach from=0|fn_get_plain_categories_tree:false item="cat"}
                                {if $cat.status == "A"}
                                        <option    value="{$cat.category_id}" {if $addons.twigmo.home_page_content == $cat.category_id}selected="selected"{/if}>{$cat.category|escape|indent:$cat.level:"&#166;&nbsp;&nbsp;&nbsp;&nbsp;":"&#166;--&nbsp;" nofilter}</option>
                                {/if}
                        {/foreach}
                </select>
                {include file="buttons/button.tpl" but_text=__("twgadmin_edit_these_blocks") but_href="block_manager.manage&selected_location=`$locations_info.index`&s_layout=`$default_layout_id`" but_id="elm_edit_home_page_blocks" but_role="link" but_meta="hidden"  but_target="_blank"}
                {include file="buttons/button.tpl" but_text=__("twgadmin_edit_these_blocks") but_href="block_manager.manage&selected_location=`$locations_info.twigmo`&s_layout=`$default_layout_id`" but_id="elm_edit_tw_home_page_blocks" but_role="link" but_meta="hidden"  but_target="_blank"}
        </div>
    </div>

    {* Geolocation *}
    <div class="form-field control-group">
        <label class="control-label" for="elm_tw_geolocation">{__("twgadmin_enable_geolocation")}:</label>
        <div  class="controls">
                <input type="hidden" name="tw_settings[geolocation]" value="N" />
                <input type="checkbox" class="checkbox" id="elm_tw_geolocation" name="tw_settings[geolocation]" value="Y" {if $addons.twigmo.geolocation != "N"}checked="checked"{/if} />

        </div>
    </div>

    {* Logo *}
    <div class="form-field control-group">
        <label class="control-label">{__("twgadmin_mobile_logo")}:</label>
        <div class="controls">
            <div class="float-left">
                {include file="common/fileuploader.tpl" var_name="tw_settings[logo]" image=true}
            </div>
            <div class="float-left attach-images-alt logo-image">
                <img class="solid-border" src="{$addons.twigmo.logoURL|default:$default_logo}" />
            </div>
        </div>
    </div>

    {* Favicon *}
    <div class="form-field control-group">
        <label class="control-label">{__("twgadmin_mobile_favicon")}:</label>
        <div class="controls">
            <div class="float-left">
                {include file="common/fileuploader.tpl" var_name="tw_settings[favicon]" image=true}
            </div>
            <div class="float-left attach-images-alt logo-image">
                <img class="solid-border" src="{$favicon}" />
            </div>
        </div>
    </div>

    {* Select skin *}
    <div class="form-field control-group">
        <label class="control-label" for="elm_tw_select_skin">{__("twgadmin_select_skin")}:</label>
        <div  class="controls">
            <select id="elm_tw_select_skin" name="tw_settings[selected_skin]">
                <option    value="default" {if $addons.twigmo.selected_skin == "default"}selected="selected"{/if}>- {__("twg_admin_basic_skin")} -</option>
            </select>
        {assign var="prevImgHeight" value="200px"}
        <div id="skinPrevWrapper" style="margin: 15px 0 0 0" class=""></div>
        <div id="skinsPrevContainer" class="hidden">
                <img style="max-height: {$prevImgHeight}" id="skinPrev_default" src="{$images_dir}/addons/twigmo/images/skins/default.jpg">
        </div>
        </div>
    </div>


    {* Show only required profile fields *}
    <div class="form-field control-group">
        <label class="control-label" for="elm_tw_only_req_profile_fields">{__("twgadmin_only_req_profile_fields")}:</label>
        <div  class="controls">
                <input type="hidden" name="tw_settings[only_req_profile_fields]" value="N" />
                <input type="checkbox" class="checkbox" id="elm_tw_only_req_profile_fields" name="tw_settings[only_req_profile_fields]" value="Y" {if $addons.twigmo.only_req_profile_fields == "Y"}checked="checked"{/if} />
        </div>
    </div>

    {* Url for facebook *}
    <div class="form-field control-group">
        <label class="control-label" for="elm_tw_url_for_facebook">{__("twgadmin_url_for_facebook")}:</label>
        <div  class="controls">
            <input id="elm_tw_url_for_facebook" type="text" name="tw_settings[url_for_facebook]" size="30" value="{$addons.twigmo.url_for_facebook}" class="input-text" />
        </div>
    </div>

    {* Url for twitter *}
    <div class="form-field control-group">
        <label class="control-label" for="elm_tw_url_for_twitter">{__("twgadmin_url_for_twitter")}:</label>
        <div  class="controls">
            <input id="elm_tw_url_for_twitter" type="text" name="tw_settings[url_for_twitter]" size="30" value="{$addons.twigmo.url_for_twitter}" class="input-text" />
        </div>
    </div>

    {* Url on appstore *}
    <div class="form-field control-group">
        <label class="control-label" for="elm_tw_url_for_appstore">{__("twgadmin_url_on_appstore")}:</label>
        <div  class="controls">
            <input id="elm_tw_url_on_appstore" type="text" name="tw_settings[url_on_appstore]" size="30" value="{$addons.twigmo.url_on_appstore}" class="input-text" />
        </div>
    </div>

    {* Url on googleplay *}
    <div class="form-field control-group">
        <label class="control-label" for="elm_tw_url_on_googleplay">{__("twgadmin_url_on_googleplay")}:</label>
        <div  class="controls">
            <input id="elm_tw_url_on_googleplay" type="text" name="tw_settings[url_on_googleplay]" size="30" value="{$addons.twigmo.url_on_googleplay}" class="input-text" />
        </div>
    </div>

    <script>
    //<![CDATA[
    {literal}
    Tygh.$((function (_) {
        var imgHeight = '{/literal}{$prevImgHeight}{literal}';

        _('.form-field a.text-button-link').css({'margin': '0 0 0 10px'});

        _("#elm_tw_home_page_content").bind('change', function () {fn_tw_show_block_link();}).change();

        _('#elm_tw_select_skin').bind('change', function () {fn_tw_show_skin_preview();}).change();

        _.getScript("{/literal}{$smarty.const.TWIGMO_SKINS_CONFIG_URL}{$smarty.const.TWIGMO_VERSION}/skins.js{literal}", function () {
            var selectedSkin = '{/literal}{$addons.twigmo.selected_skin}{literal}',
                            i;
            _('#elm_tw_select_skin, #skinsPrevContainer').empty();
            if ('TWGGlobal' in window){
                for (i in TWGGlobal.skins){
                    _('#elm_tw_select_skin').append('<option value="' + i + '">- ' + TWGGlobal.skins[i].name + ' -</option>');
                    _('#skinsPrevContainer').append('<img style="max-height:' + imgHeight + '" id="skinPrev_' + i + '" src="{/literal}{$smarty.const.TWIGMO_SKINS_CONFIG_URL}{$smarty.const.TWIGMO_VERSION}{literal}/' + i + '.jpg">');
                }
            } else {
                _('#elm_tw_select_skin').append('<option value="default">- Default -</option>');
                _('#skinsPrevContainer').append('<img height="' + imgHeight + '" id="skinPrev_default" src="skins/basic/admin/addons/twigmo/images/skins/default.jpg">');
            }
            _('#elm_tw_select_skin').val(selectedSkin).change();
        });

        function fn_tw_show_block_link(){
            var value = _('#elm_tw_home_page_content option:selected').val();
            if ((value === 'home_page_blocks') || (value === 'tw_home_page_blocks')) {
                if (value === 'home_page_blocks') {
                    _('#elm_edit_home_page_blocks').show();
                    _('#elm_edit_tw_home_page_blocks').hide();
                } else {
                    _('#elm_edit_tw_home_page_blocks').show();
                    _('#elm_edit_home_page_blocks').hide();
                }
            } else {
                _('#elm_edit_home_page_blocks').hide();
                _('#elm_edit_tw_home_page_blocks').hide();
            }

            return true;
        }

        function fn_tw_show_skin_preview() {
            var value = _('#elm_tw_select_skin option:selected').val();
            _('#skinsPrevContainer').append(_('#skinPrevWrapper').html());
            _('#skinPrevWrapper').empty().append(_('#skinPrev_' + value));
            return false;
        }
    })(Tygh.$));
    {/literal}
    //]]>
    </script>

</fieldset>

{/if}

<!--storefront_settings--></div>
