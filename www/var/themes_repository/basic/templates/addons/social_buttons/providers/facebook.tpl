{if $addons.social_buttons.facebook_enable == "Y" && $provider_settings.facebook.data}
<div id="fb-root"></div>
<script>(function(d, s, id) {
//<![CDATA[
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/{$addons.social_buttons.facebook_lang}/all.js#xfbml=1&appId={$addons.social_buttons.facebook_app_id}";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
//]]>
</script>
<div class="fb-like" {$provider_settings.facebook.data}></div>
{/if}
