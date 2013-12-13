{** block-description:carousel **}

{if $items}
<div class="cm-slider" id="banner_slider_{$block.snapping_id}">
    <div class="cm-slider-window">
        <div class="cm-slide-page-reel">
        {foreach from=$items item="banner" key="key"}
            <div class="cm-slide-page">
            {if $banner.type == "G" && $banner.main_pair.image_id}
                {if $banner.url != ""}<a href="{$banner.url|fn_url}" {if $banner.target == "B"}target="_blank"{/if}>{/if}
                {include file="common/image.tpl" images=$banner.main_pair}
                {if $banner.url != ""}</a>{/if}
            {else}
                <div class="wysiwyg-content">
                    {$banner.description nofilter}
                </div>
            {/if}
            </div>
        {/foreach}
        </div>
    </div>
    <div class="cm-paging {if $block.properties.navigation == "D"}cm-paging-dots{/if}">
        {foreach from=$items item="banner" key="key" name="banners"}
        {if $block.properties.navigation == "P"}
            <a data-ca-banner-iteration="{$smarty.foreach.banners.iteration}" href="#">{$smarty.foreach.banners.iteration}</a>
        {else}
            <a data-ca-banner-iteration="{$smarty.foreach.banners.iteration}" href="#"><i>&nbsp;</i></a>
        {/if}
        {/foreach}
    </div>
</div>
{/if}

<script type="text/javascript">
//<![CDATA[
(function(_, $) {
    $.ceEvent('on', 'ce.commoninit', function(context) {
        var slider = context.find('#banner_slider_{$block.snapping_id}');
        if (slider.length) {
            slider.bannerSlider({
                delay: {math equation="s*1000" s=$block.properties.delay|default:0},
                navigation: {if $items|count > 1}'{$block.properties.navigation|default:'N'}'{else}'N'{/if}
            });
        }
    });
}(Tygh, Tygh.$));
//]]>
</script>