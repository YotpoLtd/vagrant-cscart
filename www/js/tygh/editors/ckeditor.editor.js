/* editior-description:text_ckeditor */

(function(_, $) {
    $.ceEditor('handlers', {

		editorName: 'ckeditor',
		params: null,
        
        run: function(elm, params) {
            CKEDITOR_BASEPATH = _.current_location + '/js/lib/ckeditor/';
            
            if (typeof(window.CKEDITOR) == 'undefined') {
                $.ceEditor('state', 'loading');
                return $.getScript('js/lib/ckeditor/ckeditor.js', function() {
                    $.ceEditor('state', 'loaded');
                    elm.ceEditor('run', params);
                });
            }
                
			if (!this.params) {
				this.params = {
					toolbar: [['Format','Font','FontSize', 'Bold','Italic','Underline','TextColor','BGColor','-','Link','Image','-','NumberedList','BulletedList','Indent','Outdent','JustifyLeft','JustifyCenter','JustifyRight','-','Source']],
					bodyClass: 'wysiwyg-content',
					contentsCss: $.ceEditor('content_css'),
					filebrowserBrowseUrl : _.current_location + '/js/lib/elfinder/elfinder.ckeditor.html',
					filebrowserWindowWidth : '600',
					filebrowserWindowHeight : '500'
				};

				if (typeof params !== 'undefined' && params[this.editorName]) {
					$.extend(this.params, params[this.editorName]);
				}
			}
				
			CKEDITOR.replace(elm.prop('id'), this.params);
        },

        destroy: function(elm) {
            if (typeof(CKEDITOR.instances[elm.prop('id')]) != 'undefined') {
                CKEDITOR.instances[elm.prop('id')].destroy();
            }
        },

        updateTextFields: function(elm) {
            if (typeof(window.CKEDITOR) != 'undefined') {
                if (typeof(CKEDITOR.instances[elm.prop('id')]) != 'undefined') {
                    CKEDITOR.instances[elm.prop('id')].updateElement();
                }
            }
        },

        recover: function(elm) {
            $.ceEditor('run', elm);
        },
        
        val: function(elm, value) {
            if (typeof(value) == 'undefined') {
                return CKEDITOR.instances[elm.prop('id')].getData();
            } else {
                CKEDITOR.instances[elm.prop('id')].setData(value);
            }
            return true;
        },

        disable: function(elm, value) {
            if (typeof(window.CKEDITOR) != 'undefined') {
                CKEDITOR.instances[elm.prop('id')].setReadOnly(value);
            }
        }
    });
}(Tygh, Tygh.$));
