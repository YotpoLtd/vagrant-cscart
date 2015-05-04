/* editior-description:text_tinymce */
(function(_, $) {
	$.ceEditor('handlers', {

		editorName: 'tinymce',
		params: null,
        is_destroying: false,
		
		run: function(elm, params) {
			if (typeof($.fn.tinymce) == 'undefined') {
				$.ceEditor('state', 'loading');
				return $.getScript('js/lib/tinymce/jquery.tinymce.js', function() {
					$.ceEditor('state', 'loaded');
					elm.ceEditor('run', params);
				});
			}
			
			// You have to change this array if you want to add a new lang pack.
			var support_langs = ['ar', 'az', 'be', 'bg', 'bn', 'br', 'bs', 'ca', 'ch', 'cs', 'cy', 'da', 'de', 'dv', 'el', 'en', 'es', 'et', 'eu', 'fa', 'fi', 'fr', 'gl', 'gu', 'he', 'hi', 'hr', 'hu', 'hy', 'ia', 'id', 'ii', 'is', 'it', 'ja', 'ka', 'kl', 'ko', 'lb', 'lt', 'lv', 'mk', 'ml', 'mn', 'ms', 'nb', 'nl', 'nn', 'no', 'pl', 'ps', 'pt', 'ro', 'ru', 'sc', 'se', 'si', 'sk', 'sl', 'sq', 'sr', 'sv', 'ta', 'te', 'th', 'tr', 'tt', 'tw', 'uk', 'ur', 'vi', 'zh', 'zu'];

			var lang = fn_get_listed_lang(support_langs);

			if (!this.params) {
				this.params = {
					script_url : _.current_location + '/js/lib/tinymce/tiny_mce.js',
					plugins : 'safari,style,advimage,advlink,xhtmlxtras,inlinepopups',
					theme_advanced_buttons1: 'formatselect,fontselect,fontsizeselect,bold,italic,underline,forecolor,backcolor,|,link,image,|,numlist,bullist,indent,outdent,justifyleft,justifycenter,justifyright,|,code',
					theme_advanced_buttons2: '',
					theme_advanced_buttons3: '',
					theme_advanced_toolbar_location : 'top',
					theme_advanced_toolbar_align : 'left',
					theme_advanced_statusbar_location : 'bottom',
					theme_advanced_resizing : true,
					theme_advanced_resize_horizontal : false,
					theme : 'advanced',
					language: lang,
					strict_loading_mode: true,
					convert_urls: false,
					remove_script_host: false,
					body_class: 'wysiwyg-content',
					content_css: $.ceEditor('content_css').join(),
		
					file_browser_callback : function(field_name, url, type, win) {
						tinyMCE.activeEditor.windowManager.open({
							file : _.current_location + '/js/lib/elfinder/elfinder.tinymce.html',
							width : 600,
							height : 450,
							resizable : 'yes',
							inline : 'yes',
							close_previous : 'no',
							popup_css : false // Disable TinyMCE's default popup CSS
						}, {
							'window': win,
							'input': field_name,
							'current_location': _.current_location + '/',
							'connector_url': fn_url('elf_connector.images?ajax_custom=1')
						});
					},
                    setup: function(ed) {
                        ed.onInit.add(function(ed) {
                            if ($(elm).prop('disabled')) {
                                elm.ceEditor('disable', true);
                            }
                        });
                    }
				};

				if (typeof params !== 'undefined' && params[this.editorName]) {
					$.extend(this.params, params[this.editorName]);
				}
			}

			elm.tinymce(this.params);
		},
	
		destroy: function(elm) {
            tinyMCE.execCommand('mceRemoveControl', false, elm.prop('id'));
            this.is_destroying = true;
            setTimeout(function() {
                // TinyMCE editor disappears by timeout after destroy, even if editor is recovered
                // add delay to track it
                this.is_destroying = false;
            }, 1);
		},
	
		recover: function(elm) {
            if (this.is_destroying) {
                setTimeout(function() {
                    tinyMCE.execCommand('mceAddControl', false, elm.prop('id'));
                }, 1);
            } else {
                tinyMCE.execCommand('mceAddControl', false, elm.prop('id'));
            }
        },
               
        val: function(elm, value) {
            if (typeof(value) == 'undefined') {
                return $(elm).val();
            } else {
                $(elm).val(value);
            }
            
            return true;
        },

        updateTextFields: function(elm) {
            return true;
        },

        disable: function(elm, value) {
            var state = (value == true) ? 'Off' : 'On';
            tinyMCE.editors[0].getBody().setAttribute('contenteditable', !value);
            tinyMCE.editors[0].controlManager.get('fontselect').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('fontsizeselect').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('bold').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('italic').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('underline').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('forecolor').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('backcolor').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('link').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('image').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('numlist').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('bullist').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('indent').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('outdent').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('justifyleft').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('justifycenter').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('justifyright').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('code').setDisabled(value);
            tinyMCE.editors[0].controlManager.get('formatselect').setDisabled(value);
        }
	});
}(Tygh, Tygh.$));
