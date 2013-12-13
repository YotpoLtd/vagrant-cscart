{if $addons.social_buttons.twitter_enable == "Y" && $provider_settings.twitter.data}
<a href="https://twitter.com/share" class="twitter-share-button" {$provider_settings.twitter.data nofilter}>Tweet</a>

<script type="text/javascript">
//<![CDATA[
    !function(d,s,id){
        var js,fjs=d.getElementsByTagName(s)[0];
        if(!d.getElementById(id)){$ldelim}
            js=d.createElement(s);
            js.id=id;
            js.src="https://platform.twitter.com/widgets.js";
            fjs.parentNode.insertBefore(js,fjs);
        }
    }(document,"script","twitter-wjs");
//]]>
</script>
{/if}
