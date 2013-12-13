(function(_, $) {

    var translate_lang_code = '';
    var translate_obj = {};


    function _showTranslateBox()
    {
        translate_obj.edited = true;
        var trans_box = $('#translate_box');

        var sl = $('#translate_box_language_selector');
        if (sl.children().length) {
            $('ul.cm-select-list a', sl).addClass('cm-lang-link');
        }

        if (!translate_lang_code) {
            translate_lang_code = _.cart_language;
        }

        _changeLanguage(translate_lang_code);
        _switchLangvar(translate_lang_code);

        trans_box.ceDialog('open', {
            title: $('#translate_dialog_header').prop('title'),
            height: 'auto',
            width: 'auto'
        });
    
        // fix z-index to display translation box above notificatios and  etc.
        trans_box.dialog('widget').css('z-index', 1100);
    }

    function _setPhrase(new_phrase)
    {
        $('[data-ca-translate="' + translate_obj.var_name + '"]').each(function(){
            var jelm = $(this);
            if (jelm.is('var.cm-translate, option')) {
                jelm.html(new_phrase);
            } else if (jelm.is('input[type=checkbox]')) {
                jelm.prop('title', new_phrase);
            } else if (jelm.is('input[type=image], img')) {
                jelm.prop('title', new_phrase);
                jelm.prop('alt', new_phrase);
            } else {
                jelm.val(new_phrase);
            }
        });
    }

    function _changeLanguage(lang_code)
    {
        var sl = $('#translate_box_language_selector');
        var old_lang_code = translate_lang_code;
        if (sl.children().length) {
            var jelm = $('a[name=' + lang_code +']', sl);
            var old_elm = $('a[name=' + old_lang_code +']', sl);
            if (jelm.data('caCountryCode') && old_elm.data('caCountryCode')) {
                $('i.flag-' + old_elm.data('caCountryCode'), sl).first().removeClass('flag-' + old_elm.data('caCountryCode')).addClass('flag-' + jelm.data('caCountryCode'));
            }
            old_elm.removeClass('active');
            jelm.addClass('active');
            $('a.cm-combination span', sl).text(jelm.text()); // set new text
        }

        translate_lang_code = lang_code;
    }

    function _switchLangvar(cur_lang)
    {
        if ((_.cart_language == cur_lang) && !$(translate_obj.target_obj).hasClass('cm-pre-ajax') && !$('option[value="' + translate_obj.target_obj.val() + '"]', translate_obj.target_obj).hasClass('cm-pre-ajax')) {
            $('#trans_val').val(translate_obj.phrase);
            $('#orig_phrase').text(translate_obj.phrase);

        } else {
            $('#trans_val').val('');
            $('#orig_phrase').html('&nbsp;');
            _setPhrase(translate_obj.phrase);
            $.ceAjax('request', fn_url('design_mode.get_langvar'), {data:{langvar_name: translate_obj.var_name, lang_code: cur_lang}, callback: _swithLangvarCallback});
        }
    }

    function _swithLangvarCallback(data)
    {
        var phrase = data.langvar_value.indexOf('[lang') == 0 ? data.langvar_value.substring(data.langvar_value.indexOf(']') + 1, data.langvar_value.lastIndexOf('[')) : data.langvar_value;
        $('#trans_val').val(phrase);
        $('#orig_phrase').text(phrase);
    }

    function _savePhrase()
    {
        $.ceAjax('request', fn_url('design_mode.update_langvar'), {method: 'post', data: {langvar_name: translate_obj.var_name, langvar_value: $('#trans_val').val(), lang_code: translate_lang_code}});
        translate_obj = null;
        $('#translate_box').dialog('close');
    }

    function _cancelPhrase()
    {
        _setPhrase(translate_obj.old_phrase);
        translate_obj = null;
    }

    var methods = {
        dispatch: function(e)
        {
            var jelm = $(e.target);

            if (e.type == 'click' && $.browser.mozilla && e.which != 1) {
                return true;
            }
            if (e.type == 'click') {
                if (jelm.closest('.cm-icon-translate').length) {
                    var t_elm = $('.cm-translate', jelm.closest('var')).first();
                    
                    if (t_elm.is('select')) {
                        t_elm = $('option[value="' + t_elm.val() + '"]', t_elm);
                    }

                    if (!t_elm.length || !t_elm.data('caTranslate')) {
                        return;
                    }

                    var phrase = '';
                    
                    if (t_elm.is('var, option')) {
                        phrase = t_elm.html();
                    } else if (!t_elm.is('input[type=checkbox]') && !t_elm.is('input[type=image]') && !t_elm.is('img')) {
                        phrase = t_elm.val();
                    } else {
                        phrase = t_elm.prop('title');
                    }

                    translate_obj = {'var_name': t_elm.data('caTranslate'), 'phrase': phrase, 'target_obj': t_elm, 'edited': false, 'old_phrase': phrase};

                    _showTranslateBox();
                    
                } else if (((jelm.closest('.cm-popup-switch').length) && jelm.closest('#translate_box').length) && translate_obj && translate_obj.edited && !$('#translate_box:visible').length) {
                    _setPhrase(translate_obj.phrase);
                    translate_obj.edited = false;

                } else if (jelm.hasClass('cm-lang-link') && jelm.parents('.cm-select-list').length) {
                    _changeLanguage(jelm.prop('name'));
                    $.ceAjax('request', jelm.prop('href'), {data: {langvar_name: translate_obj.var_name}, caching: false, callback: _swithLangvarCallback});
                    jelm.parents('.cm-popup-box:first').hide();
                    return false;
                        
                } else if (jelm.closest('.cm-translate-save').length) {
                    _savePhrase();

                } else if (jelm.closest('.cm-translate-cancel').length) {
                    _cancelPhrase();
                }
            } 
        },
 
        load: function(content)
        {
            // add translation icon
            $('.cm-load-translate', content).each(function() {
                elm = $(this);
                
                if (elm.is('option')) {
                    if (!elm.hasClass('cm-load-translate')) {
                        return true;
                    }

                    elm = elm.closest('select');
                    $('option', elm).removeClass('cm-load-translate');
                }
                
                elm.wrap('<var class="translate-wrap">');
                elm.before('<i class="cm-icon-translate icon-translate"></i>');

                elm.removeClass('cm-load-translate');
                elm.addClass('cm-translate translate-item');
            });

            $('.cm-translate').parents('.cm-button-main').removeClass('cm-button-main');

            $('.cm-translate:has(p,div,ul)').css('display', 'block');
            if ($.browser.msie) {
                $('.cm-translate:has(p)').each(function() {
                    $(this).html($(this).html());
                });
            }
        },

        init: function(content)
        {
            $(_.doc).on('click', '.cm-icon-translate', function(e) {
                // attach translation icon click processing with highest priority
                // to prevent processing of events attached to translation icon parents
                e.stopPropagation();
                e.preventDefault();
                return $.ceTranslationMode('dispatch', e);
            });

            $(_.doc).on('click', function(e) {
                return $.ceTranslationMode('dispatch', e);
            });
            
            $('#trans_val').on('keyup', function(e) {
                if (_.cart_language == translate_lang_code) {
                    _setPhrase($(this).val());
                }
            });
        }
    };

    $.ceTranslationMode = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if ( typeof method === 'object' || ! method ) {
                return methods.init.apply(this, arguments);
        } else {
            $.error('ty.ceTranslationMode: method ' +  method + ' does not exist');
        }
    };

    $.ceEvent('on', 'ce.commoninit', function(content) {
        $.ceTranslationMode('load', content);
    });

    $(document).ready(function() {
        $.ceTranslationMode();
    });
    
}(Tygh, Tygh.$));
