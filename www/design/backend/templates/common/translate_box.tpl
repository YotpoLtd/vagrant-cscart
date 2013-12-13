<div id="translate_link" class="hidden">
    <img src="{$images_dir}/translate_icon.png" width="16" height="16" border="0" alt="{__("translate")}" title="{__("translate")}" onclick="fn_show_translate_box();" />
</div>
<div id="translate_box" class="hidden">

    <div id="translate_box_menu_language_selector" class="float-right">
        {if !"ULTIMATE:FREE"|fn_allowed_for}
        {include file="common/select_object.tpl" style="graphic" link_tpl="design_mode.get_langvar"|fn_link_attach:"lang_code=" items=$languages selected_id=$smarty.const.CART_LANGUAGE key_name="name" suffix="translate_box" display_icons=true select_container_id="translate_box_language_selector"}
        {/if}
    </div>

    <input id="tbox_descr_sl" type="hidden" name="descr_sl" value="" />
    <input id="trans_val" class="input-text" type="text" value="" size="37"/>
    <p></p>
    <div id="orig_phrase" class="clear-both"></div>

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" but_type="button" but_meta="cm-translate-save" but_text=__("update_text") cancel_action="close"}
    </div>

</div>
{script src="js/tygh/translation_mode.js"}