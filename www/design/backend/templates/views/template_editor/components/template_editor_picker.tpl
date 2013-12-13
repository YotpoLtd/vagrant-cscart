<div class="hiddens" id="template_editor_content" title="{__("template_editor")}">

<div id="template_text"></div>

<div class="buttons-container">
    {include file="buttons/add_close.tpl" is_js=true but_close_text=__("save") but_close_onclick="fn_save_template();" but_onclick="template_editor.restore_file();" but_text=__("restore_from_repository")}
</div>

</div>

{script src="js/lib/ace/ace.js"}
{script src="js/tygh/design_mode.js"}

<script type="text/javascript">
//<![CDATA[
Tygh.tr('text_page_changed', '{__("text_page_changed")|escape:"javascript"}');
Tygh.tr('text_restore_question', '{__("text_restore_question")|escape:"javascript"}');
Tygh.tr('text_template_changed', '{__("text_template_changed")|escape:"javascript"}');
//]]>
</script>
