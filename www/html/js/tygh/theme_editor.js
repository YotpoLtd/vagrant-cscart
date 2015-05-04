(function(_, $) {

    var link_iterator = 0;
    var disable_value_changer = false;
    var preset_changed = false;
    var form_initial_state = '';
    var patterns_url = 'http://patterns.cart-services.com';

    // Do not initialize in embedded mode
    if (_.embedded) {
        return false;
    }

    function formParams()
    {
        var elms = $('[name^="preset[data]"]', $('#theme_editor'));
        var s = '';
        var params = [];

        elms.each(function() {
            var self = $(this);

            if (self.hasClass('cm-te-skip-css')) {
                return;
            }

            if (self.is('input[type=checkbox]') && !self.prop('checked')) {
                return;
            }

            if (self.is('input[type=radio]') && !self.prop('checked')) {
                return;
            }

            params[self.prop('name')] = self.val();
        });

        for (var k in params) {
            s += '&' + escape(k) + '=' + escape(params[k]);
        }

        return s;
    }

    function updateCss(url)
    {
        link_iterator++;

        $.toggleStatusBox('show');

        var link = $('<link/>', {
            type: 'text/css',
            rel: 'stylesheet',
            media: 'screen',
            id: 'theme_editor_css_' + link_iterator
        }).appendTo('head');


        link.prop('href', $.attachToUrl(url, 'x=' + Math.random()));

        link.on('load', function() {
            // We should keep 2 link elements to avoid flickering when styles are reloaded
            var obsolete_link = $('#theme_editor_css_' + (link_iterator - 1));
            if (obsolete_link.length) {
                obsolete_link.remove();
            }
                        
            $.toggleStatusBox('hide');
        });
    }

    function serializeForm()
    {
        var form = $('form[name=theme_editor_form]');
        var serialized_data = $('[name^="preset[data]"],[name^="preset[custom_css]"]', form).serialize();
        $('input[type=file]', form).each(function() {
            serialized_data += $(this).val();
        });

        return serialized_data;
    }

    function isFormChanged()
    {
        if (serializeForm() == form_initial_state) {
            return false;
        }

        return true;
    }

    function setPresetStatus(status)
    {
        var s_elm = $('#theme_editor .cm-te-load-preset.active');
        var t_elm = $('#theme_editor span.cm-preset-title');
        var text = s_elm.html();
        var changed_text = ' *';

        if (!text) {
            return false;
        }

        if (status == 'changed') {
            preset_changed = true;
            if (text.indexOf(changed_text) === -1) {
                s_elm.html(text + changed_text);
                t_elm.html(text + changed_text);
            }
        } else if (status === 'clear') {
            preset_changed = false;
            if (text.indexOf(changed_text) !== -1) {
                s_elm.html(text.str_replace(changed_text, ''));
                t_elm.html(text.str_replace(changed_text, ''));
            }
        }

        return true;
    }

    function getUrlFromCss(prop)
    {
        var url = prop.str_replace('url(', '');
        url = url.str_replace(')', '');
        url = url.str_replace('"', '');

        return url;
    }

    function initPatterns()
    {
        $.ceAjax('request', patterns_url, {
            hidden: true,
            callback: function(data) {
                var p_open = '<li><div class="te-pattern-preview cm-te-select-pattern"><img width="100%" height="100%" src="';
                var p_close = '"></div></li>';
                var li = p_open + data.join(p_close + p_open) + p_close;
                $('ul.cm-te-pattern-list').append($(li));
            }
        });
    }

    function initGoogleFonts()
    {
        if (!('google_fonts' in _)) {
            return false;
        }

        var li = '';
        var container = $('.cm-te-google');
        var step = 28;

        for(var i = 0; i < _.google_fonts.length; i++) {
            li += '<li class="cm-te-google-font te-google-font" data-ca-select-box-value="'+ _.google_fonts[i] +'">' + '<span style="background-position: 0 -' + (i * step) +'px">' + _.google_fonts[i] + '</span>' + '</li>';
        }

        container.each(function() {
            var self = $(this);
            var elms = self.find('.cm-te-google-elements');
            elms.append(li);
            var active = elms.find('li[data-ca-select-box-value="' + self.data('caSelectBoxDefault') + '"]');
            if (active.length) {
                active.addClass('active');
                self.find('span:first').html(active.html());
            }
        });
    }

    function toggleSectionScroll()
    {
        var container = $('.cm-te-disable-scroll');
        if (container.css('overflow-y') == 'auto') {
            container.css('overflow-y','hidden');
        } else {
            container.css('overflow-y','auto');
        }
    }

    function validPresetName()
    {
        var preset_name = prompt(_.tr('theme_editor.preset_name'), '');
        if (!preset_name) {
            return false;
        }

        var existing_presets = $('#elm_te_presets li a').each(function() {
            if ($(this).html().toLowerCase() == preset_name.toLowerCase()) {
                preset_name = '';
                $.ceNotification('show', {
                    type: 'E',
                    title: _.tr('error'),
                    message: _.tr('theme_editor.error_preset_exists')
                });

                return false;
            }
        });

        if (preset_name && (!preset_name.match(/^[^\\\#\%\/\?\*:;\{\}]+$/) || !preset_name.trim())) {
            $.ceNotification('show', {
                type: 'E',
                title: _.tr('error'),
                message: _.tr('theme_editor.incorrect_preset_name')
            });

            return false;
        }

        return preset_name;
    }

    $(document).ready(function(){

        $.ceAjax('request', fn_url('theme_editor.view'), {
            result_ids: 'theme_editor',
            data: {
                theme_url: _.current_location + '/' + _.current_url
            },
            callback: function() {

                var current_css = $('link[href*=standalone]');
                var css_filename = current_css.length ? current_css.prop('href').split('/').pop() : 'standalone.css'; // support for dev_css dev mode
                var editor_url = 'theme_editor.get_css?css_filename=' + encodeURIComponent(css_filename) + '&';

                // Patterns
                $('#theme_editor').on('click', '.cm-te-select-pattern', function(e) {
                    var self = $(this);
                    var parent = self.closest('.cm-te-pattern-list');
                    var value_holder = $('#' + parent.data('caHolderId'));
                    var self_img = self.find('img');

                    if (self_img.length) {
                        value_holder.val(self_img.prop('src')).change();
                    } else { // transparent
                        value_holder.val('transparent').change();
                    }

                    self.closest('.cm-te-patterns-container').hide();
                    toggleSectionScroll();
                });

                $('#theme_editor').on('change', '.cm-te-pattern-holder', function() {
                    var self = $(this);
                    var preview = $('#' + self.data('caPreviewId'));
                    var preview_img = preview.find('img');

                    if(preview_img.length == 0 && self.val() !== 'transparent') {
                        preview.empty().append('<img height="100%" width="100%" src="'+ self.val() +'"/>');
                    }

                    if (!self.val() || self.val() == 'transparent') {
                        preview_img.hide();
                        preview.addClass('te-pattern-empty').append('<i class="icon-image"></i>');
                    } else {
                        preview.removeClass('te-pattern-empty');
                        preview_img.prop('src', self.val()).show();
                    }
                });

                
                $('#theme_editor').on('click', '.cm-te-pattern-selector', function() {
                    var dlg = $('#' +  $(this).data('caPatternDialog'));

                    // Calculate initial position
                    dlg.css({
                        'top': $(this).offset().top - $(document).scrollTop() + $(this).height()
                    });

                    // Hide containers without this
                    $('.cm-te-patterns-container').not(dlg).hide();

                    if (dlg.is(':visible')) {
                        dlg.hide();
                    } else {
                        dlg.show();
                    }

                    toggleSectionScroll();
                });


                $('#theme_editor').on('click', function(e) {
                    var elm = $(e.target);
                    if (!elm.closest('.cm-te-pattern-selector,.cm-te-patterns-container').length) {
                        $('.cm-te-pattern-selector').each(function() {
                            var self = $(this);
                            var dlg = $('#' +  self.data('caPatternDialog'));
                            if (dlg.is(':visible')) {
                                dlg.hide();
                                toggleSectionScroll();
                            }
                        });
                    }
                });

                // Google fonts
                $.getJSON('js/tygh/google_fonts_list.js', function(data) {
                    _.google_fonts = data;
                    initGoogleFonts();
                });

                $('#theme_editor').on('click', '.cm-te-google-font', function() {
                    var self = $(this);
                    $('link:last').after('<link href="http://fonts.googleapis.com/css?family=' + self.data('caSelectBoxValue') + '" rel="stylesheet" type="text/css">');
                    self.removeClass('cm-te-google-font');
                });

                // FIXME: this event catches logout link click
                $(_.doc).on('click', 'a.account,a.relogin,.cm-te-change-layout', function(e) {
                    e.stopImmediatePropagation();
                    return true;
                });

                $('#theme_editor').on('click', '.cm-te-close-editor', function(e) {
                    e.stopImmediatePropagation();
                    var langvar = preset_changed ? _.tr('theme_editor.text_close_editor_unsaved') : _.tr('theme_editor.text_close_editor');

                    if (confirm(langvar)) {
                        var self = $(this);
                        self.prop('href', $.attachToUrl(self.prop('href'), 'redirect_url=' + escape($('input[name=redirect_url]:first').val())));
                        return true;
                    }

                    return false;
                });

                $.ceEvent('on', 'ce.colorpicker.hide', function() {
                    toggleSectionScroll();
                });

                $.ceEvent('on', 'ce.colorpicker.show', function() {
                    toggleSectionScroll();
                });
                

                $('#theme_editor').on('change', '.cm-colorpicker', function() {
                    var self = $(this);
                    var gradient = $('#' + self.prop('id') + '_gradient');
                    var custom_disable = false;

                    if (gradient.length) {
                        if (!disable_value_changer) {
                            disable_value_changer = true;
                            custom_disable = true;
                        }

                        gradient.ceColorpicker('set', self.val());

                        if (custom_disable) {
                            disable_value_changer = false;
                        }
                    }
                });

                $('#theme_editor').on('change', '.cm-te-value-changer', function() {
                    if (disable_value_changer === true) {
                        return false;
                    }

                    updateCss(fn_url(editor_url + formParams()));
                });

                $('#theme_editor').on('click', '.cm-te-load-preset', function(e) {
                    var self = $(this);
                    if (isFormChanged() && confirm(_.tr('text_changes_not_saved')) === false) {
                        return false;
                    }

                    $.ajaxLink(e, '', function() {
                        updateCss(fn_url(editor_url + 'preset_id=' + self.data('caPresetId')));
                        self.addClass('active');
                        form_initial_state = serializeForm();
                    });

                    e.preventDefault();
                    return false;
                });

                $('#theme_editor').on('click', '.cm-te-change-layout', function(e) {
                    var self = $(this);

                    if (isFormChanged() && confirm(_.tr('text_changes_not_saved')) === false) {
                        return false;
                    }
                });

                // Set changed flag
                $('#theme_editor').on('change', 'input', function() {
                    setPresetStatus('changed');
                });

                // Special for textarea
                $('#theme_editor').on('input propertychange', 'textarea', function() {
                    setPresetStatus('changed');
                });

                // Set changed flag for selectbox
                $('#theme_editor').on('change', '.cm-te-selectbox', function() {
                    setPresetStatus('changed');
                });

                // Close opened select boxes
                $('#theme_editor').on('click', function(e) {
                    if ($(e.target).hasClass('cm-te-selectbox') || $(e.target).parents('.cm-te-selectbox').length) {
                        return;
                    }

                    if ($(e.target).parents('.te-select-dropdown').length === 0) {
                        $('.te-select-dropdown:visible').hide();
                    }
                });

                // Display opened select box
                $('#theme_editor').on('click', '.cm-te-selectbox', function(e) {
                    var self = $(this);
                    var ul = self.find('ul');

                    $('ul.te-select-dropdown').not(ul).hide();

                    if (ul.is(':visible')) {
                        ul.hide();
                    } else {
                        ul.show();
                    }
                });

                // selectbox: select element
                $('#theme_editor').on('click', '.cm-te-selectbox li', function(e, stop_propagation) {
                    stop_propagation = stop_propagation || false;
                    var self = $(this);
                    var container = self.parents('.cm-te-selectbox');

                    // set selectbox value
                    container.find('input[type=text]').val(self.data('caSelectBoxValue'));
                    
                    // set selectbox title
                    container.find('span:first').html(self.text());
                    
                    // highlight active item
                    container.find('li').removeClass('active');
                    self.addClass('active');

                    if (container.hasClass('cm-te-value-changer')){
                        container.trigger('change');
                    }

                    if (stop_propagation) {
                        e.stopImmediatePropagation();
                    }
                });

                // tabs
                $('#theme_editor').on('click', '.cm-te-tabs a', function() {
                    var self = $(this);
                    var ul = self.parents('ul');
                    var container = self.parents('.cm-te-tabs');
                    
                    $('li', ul).removeClass('active');
                    $('.cm-te-tab-contents', container).hide();
                    $('#' + self.data('caTargetId')).show();

                    self.parent('li').addClass('active');
                });

                // Show editor sections
                $('#theme_editor').on('click', '.cm-te-sections li', function() {
                    $('.cm-te-section').hide();
                    $('#' + $(this).data('caTargetId')).show();
                    $('input[name=selected_section]', $('#theme_editor')).val($(this).data('caTargetId'));
                });

                // Reset button
                $('#theme_editor').on('click', '.cm-te-reset', function() {

                    result = confirm(_.tr('theme_editor.text_reset_changes'));
                    if (!result) {
                        return false;
                    }

                    var container = $(this).parents('.cm-te-section');

                    var elms = $('[name^="preset[data]"],[name^="preset[custom_css]"]', container);

                    disable_value_changer = true; // disable cm-te-value-changer event

                    elms.each(function() {
                        var self = $(this);

                        if (self.is('input[type=checkbox]') || self.is('input[type=radio]')) {
                            self.prop('checked', self.prop('defaultChecked'));
                        } else {

                            self.val(self.prop('defaultValue')).trigger('change');

                            // dirty, fix to allow selectbox work
                            if (self.hasClass('cm-te-selectbox-storage')) {
                                $('li[data-ca-select-box-value="' + self.val() + '"]', self.parents('.cm-te-selectbox')).trigger('click', [true]);
                            }

                            if (self.hasClass('cm-colorpicker')) {
                                self.ceColorpicker('reset');
                            }
                        }
                    });

                    disable_value_changer = false;

                    updateCss(fn_url(editor_url + formParams()));

                    if (isFormChanged() === false) {
                        setPresetStatus('clear');
                    }

                    return false; // prevent default action (form submit)
                });

                $('#theme_editor').on('click', '.cm-te-duplicate-preset', function() {
                    var preset_name = validPresetName();
                    if (preset_name) {
                        $.ceAjax('request', fn_url('theme_editor.duplicate'), {
                            data: {
                                preset_id: $(this).data('caPresetId'),
                                name: preset_name
                            },
                            result_ids: 'theme_editor',
                            callback: function() {
                                updateCss(fn_url(editor_url + formParams()));
                            }
                        });
                    }
                });

                // Enable embedded mode to allow navigation during theme editing
                _.embedded = true;
                _.doc = $('#' + _.init_container);
                _.body = $('#' + _.container);

                form_initial_state = serializeForm();
            }
        });
    });


    // Save theme
    $.ceEvent('on', 'ce.formpre_theme_editor_form', function(form, elm) {
        var s_name = $('input[name="preset[name]"]', form);
        var s_id = $('input[name="preset_id"]', form);

        if (s_id.data('caIsDefault')) {

            var preset_name = validPresetName();
            if (!preset_name) {
                return false;
            }

            s_id.val('');
            s_name.val(preset_name);
        }

        return true;
        
    });

    $.ceEvent('on', 'ce.formajaxpost_theme_editor_form', function() {
        $('div.cm-te-logo').each(function() {
            var self = $(this);

            if (self.data('caImageArea') && self.data('caImageArea') == 'theme') {
                $('img.logo', 'div.logo-container').prop('src', getUrlFromCss(self.css('background-image'))).css({
                    width: 'auto',
                    height: 'auto'
                });
            }

            if (self.data('caImageArea') && self.data('caImageArea') == 'favicon') {
                $('link[rel="shortcut icon"]').remove();
                $('<link rel="shortcut icon" href="' + getUrlFromCss(self.css('background-image')) + '">').appendTo('head');
            }
        });

        $('.cm-te-value-changer:first').trigger('change');

        setPresetStatus('clear');
        form_initial_state = serializeForm();
    });

    $.ceEvent('on', 'ce.commoninit', function(context) {
        if (context.find('#theme_editor_container').length) {
            initGoogleFonts();
            initPatterns();
        }
    });

    $.ceEvent('on', 'ce.switch_theme_editor_container', function(flag) {
        if (flag) {
            $('#sw_theme_editor_container').addClass('hidden');
            $('#tygh_container').removeClass('te-mode');
        } else {
            $('#sw_theme_editor_container').removeClass('hidden');
            $('#tygh_container').addClass('te-mode');
        }
    });

    // Recalculate footer height
    $.ceEvent('on', 'ce.ajaxdone', function() {
        $.stickyFooter();
    });

    // Update URL in layout selector
    $.ceEvent('on', 'ce.ajaxdone', function(elms, scripts, params, response_data, response_text) {
        if (response_data && response_data.current_url) {
            $('a.cm-te-change-layout').each(function() {
                var s = $(this);
                if (s.prop('href')) {
                    s.prop('href', $.attachToUrl(response_data.current_url, 's_layout=' + s.data('caLayoutId')));
                }
            });
        }
    });

    $(window).on('beforeunload', function(e) {
        if (isFormChanged()) {
            return _.tr('text_changes_not_saved');
        }
    });

})(Tygh, Tygh.$);
