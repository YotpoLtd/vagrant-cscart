{capture name="main_icon"}
    <a href="{"products.view?product_id=`$product.product_id`"|fn_url}">
        {include file="common/image.tpl" obj_id=$obj_id_prefix images=$product.main_pair image_width=$settings.Thumbnails.product_lists_thumbnail_width image_height=$settings.Thumbnails.product_lists_thumbnail_height}
    </a>
{/capture}

{if $product.image_pairs && $show_gallery}
    <div class="thumbs-wrapper jcarousel-skin cm-image-gallery" data-ca-items-count="1" id="icons_{$obj_id_prefix}">
        {strip}
        <ul>
            {if $product.main_pair}
                <li class="cm-gallery-item">
                    {$smarty.capture.main_icon nofilter}

                </li>
            {/if}
            {foreach from=$product.image_pairs item="image_pair"}
                {if $image_pair}
                <li class="cm-gallery-item {if $product.main_pair || !$image_pair@first}hidden{/if}">
                    <a href="{"products.view?product_id=`$product.product_id`"|fn_url}">
                        {include file="common/image.tpl" obj_id="`$obj_id_prefix`_`$image_pair.image_id`" images=$image_pair image_width=$settings.Thumbnails.product_lists_thumbnail_width image_height=$settings.Thumbnails.product_lists_thumbnail_height}
                    </a>
                </li>
                {/if}
            {/foreach}
        </ul>
        {/strip}
        {if $product.main_pair || $product.image_pairs|count > 1}
            <i class="icon-left-circle jcarousel-prev"></i>
            <i class="icon-right-circle jcarousel-next"></i>
        {/if}
    </div>
{else}
    {$smarty.capture.main_icon nofilter}
{/if}