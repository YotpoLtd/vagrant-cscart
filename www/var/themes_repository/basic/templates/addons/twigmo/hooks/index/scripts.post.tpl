{if ($smarty.session.twg_user_agent and $smarty.session.twg_user_agent == 'tablet' and $tw_settings.use_mobile_frontend == 'tablet')
        or ($smarty.session.twg_user_agent and $smarty.session.twg_user_agent == 'phone' and $tw_settings.use_mobile_frontend == 'phone')
        or ($smarty.session.twg_user_agent and ($smarty.session.twg_user_agent == 'tablet' or $smarty.session.twg_user_agent == 'phone') and $tw_settings.use_mobile_frontend == 'both_tablet_and_phone')}
    {$show_avail_notice = "Y"}
{else}
    {$show_avail_notice = "N"}
{/if}

{if $tw_settings.use_mobile_frontend != 'never' and $show_avail_notice == "Y"}
    {$device = $smarty.session.device}
    <script>
    //<![CDATA[
    {literal}
    $(function () {
        //$('.mobile-avail-notice').insertBefore('a[name="top"]');
        $('#close_notification_mobile_avail_notice').bind('click', function () {
            $(this).parents('div.mobile-avail-notice').hide();
            $.ajax({
                url: '{/literal}{"twigmo.post&close_notice=1"|fn_url nofilter}{literal}',
                dataType: 'json'
            });
        });
        if(window.devicePixelRatio){
            if(window.devicePixelRatio > 1){
                changeSizes();
            }
        }
        function changeSizes() {
            var scale = 1,
                    buttonsHeight = {/literal}{if $device == "ipad"}54{else}80{/if}{literal},
                    noticeHeight = {/literal}{if $device == "ipad"}80{else}120{/if}{literal},
                    fontSize = {/literal}{if $device == "ipad"}30{else}34{/if}{literal},
                    fontTop = {/literal}{if $device == "ipad"}15{else}18{/if}{literal},
                    buttonsTop = (noticeHeight - buttonsHeight) / 2 || 13,
                    crossTopMargin = (noticeHeight - $('#close_notification_mobile_avail_notice').height()) / 2 - buttonsTop - 2,
                    crossWidth = 30,
                    textPadding = {/literal}{if $device == "ipad"}'0 1% 0 1%'{else}'0 2% 0 2%'{/if}{literal};

            if (typeof orientation !== 'undefined' && Math.abs(orientation) === 90) {
                    scale = 0.7;
                    textPadding = '0 1% 0 1%';
            }
            $('.mobile-avail-notice a').css({'height': buttonsHeight * scale + 'px', 'line-height': buttonsHeight * scale + 'px', 'font-size': fontSize * scale + 'px', 'padding': textPadding});
            $('.mobile-avail-notice img').css({'width': crossWidth * scale + 'px !important', 'height': crossWidth * scale + 'px !important', 'margin-top': -1 * (crossWidth * scale/2) + 'px'});

        }
        window.onorientationchange = function () {
                changeSizes();
        };
        changeSizes();
    });
    {/literal}
    //]]>
    </script>
{/if}