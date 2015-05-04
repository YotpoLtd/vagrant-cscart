/* editior-description:text_redactor */
(function(_, $) {
    var methods = {
        _getEditor: function(elm) {
            var obj = $('#' + elm.prop('id'));
            if (obj.data('redactor')) {
                return obj;
            }
            
            return false;
        }
    };
    
    $.ceEditor('handlers', {

		editorName: 'redactor',
		params: null,
        elms: [],

        run: function(elm, params) {

            var translations = {'no': 'no_NB', 'zh': 'zh_tw'};
            var lang_code = (_.cart_language in translations) ? translations[_.cart_language] : _.cart_language;

            if (typeof($.fn.redactor) == 'undefined') {
                $.ceEditor('state', 'loading');
                $.ceEditor('push', elm);
                $.loadCss(['js/lib/redactor/redactor.css']);
                // Load elFinder
                $.loadCss(['js/lib/elfinder/css/elfinder.css']);

                var load_scripts = [];
                load_scripts.push($.getScript('js/lib/elfinder/js/elfinder.min.js'));
                load_scripts.push($.getScript('js/lib/redactor/redactor.min.js'));
                load_scripts.push($.getScript('js/lib/redactor/plugins/fontcolor/fontcolor.js'));
                if (lang_code != 'en') {
                    load_scripts.push($.getScript('js/lib/redactor/lang/' + lang_code + '.js'));
                }
                
                var callback = function() {
                    $.ceEditor('state', 'loaded', params);
                };
                
                $.when.apply(null, load_scripts).then(callback);

                return true;
            }

			if (!this.params) {
				this.params = {
					lang: lang_code
				};
            }

            if (typeof params !== 'undefined' && params[this.editorName]) {
                $.extend(this.params, params[this.editorName]);
            }

            this.params.initCallback = function()
            {
                this.buttonAddBefore('video', 'image', 'Image', function() {
                    // Start button processing
                    this.selectionSave();

                    var modal_image = String() +
                        '<section>' +
                            '<div id="redactor-progress" class="redactor-progress redactor-progress-striped" style="display: none;">' +
                                '<div id="redactor-progress-bar" class="redactor-progress-bar" style="width: 100%;"></div>' +
                            '</div>' +
                            '<div id="redactor_tab3" class="redactor_tab">' +
                                '<label>' + this.opts.curLang.image_web_link + '</label>' +
                                '<input type="text" name="redactor_file_link" id="redactor_file_link" class="redactor_input"  />' +
                                '<a class="redactor_modal_btn" style="margin-left: 0px; margin-top: 10px;" id="elfinder_control">'+ _.tr("browse") +'</a>' +
                            '</div>' +
                        '</section>' +
                        '<footer>' +
                            '<a href="#" class="redactor_modal_btn redactor_btn_modal_close">' + this.opts.curLang.cancel + '</a>' +
                            '<input type="button" name="upload" class="redactor_modal_btn" id="redactor_upload_btn" value="' + this.opts.curLang.insert + '" />' +
                        '</footer>';
                
                    var callback = $.proxy(function()
                    {
                        $('#redactor_upload_btn').click($.proxy(this.imageCallbackLink, this));
                        $('#redactor_file_link').focus();

                        var elf_config = {
                            url  : fn_url('elf_connector.images?ajax_custom=1')
                        };

                        $('#elfinder_control').click(function(){
                            $('<div id="elfinder_browser" />').elfinder({
                                url : fn_url('elf_connector.images?ajax_custom=1'),
                                lang : 'en',
                                dialog: {width: 900, modal: true, title: _.tr('file_browser')},
                                cutURL: _.current_location + '/',
                                closeOnEditorCallback : true,
                                places: '',
                                view: 'list',
                                disableShortcuts: true,
                                editorCallback: function(file) {
                                    $('#redactor_file_link').val(_.current_location + '/' + file);
                                }
                            });
                            $(".el-finder-dialog").css({'z-index': 50001});
                        });

                    }, this);

                    this.modalInit(this.opts.curLang.image, modal_image, 610, callback);

                    // End button processing
                });
            }

            this.params.plugins = ['fontcolor'];
            this.params.buttons = ['html', '|', 'formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                    'video', 'file', 'table', 'link', '|', 'alignment', '|', 'horizontalrule']; // 'underline', 'alignleft', 'aligncenter', 'alignright', 'justify'

            // Launch Redactor
            elm.redactor(this.params);

            if ($(elm).prop('disabled')) {
                elm.ceEditor('disable', true);
            }

            this.elms.push(elm.get(0));

            return true;
        },
        
        destroy: function(elm) {
            var ed = methods._getEditor(elm);
            if (ed) {
                ed.redactor('destroy');
            }
        },

        recover: function(elm) {
            if ($.inArray(elm.get(0), this.elms) !== -1) {
                $.ceEditor('run', elm);
            }
        },
               
        val: function(elm, value) {
            var ed = methods._getEditor(elm);
            if (!ed) {
                return false;
            }
            
            if (typeof(value) == 'undefined') {
                return ed.redactor('get');
            } else {
                ed.redactor('set', value);
            }
            return true;
        },

        updateTextFields: function(elm) {
            return true;
        },

        disable: function(elm, value) {
            var ed = methods._getEditor(elm);
            if (ed) {
                var obj = ed.redactor('getBox');
                if (value == true) {
                    if (!$(obj).parent().hasClass('disable-overlay-wrap')) {
                        $(obj).wrap("<div class='disable-overlay-wrap wysiwyg-overlay'></div>");
                        $(obj).before("<div id='" + elm.prop('id') + "_overlay' class='disable-overlay'></div>");
                        $(elm).prop('disabled', true);
                    }
                } else {
                    $(obj).unwrap();
                    $('#' + elm.prop('id') + '_overlay').remove();
                    $(elm).prop('disabled', false);
                }
            }
        }
    });
}(Tygh, Tygh.$));
