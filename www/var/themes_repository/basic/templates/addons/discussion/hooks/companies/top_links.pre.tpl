{if $discussion && $discussion.type != "D"}
    <span id="average_rating_{$object_type}_{$object_id}">
        {assign var="rating" value="rating_`$obj_id`"}{$smarty.capture.$rating nofilter}
        {if $discussion.total_posts}
            <a onclick="Tygh.$('#discussion').click(); Tygh.$.scrollToElm(Tygh.$('#content_discussion')); return false;">{$discussion.total_posts} {__("reviews", [$discussion.total_posts])}</a>
        {/if}
        {if !$discussion.disable_adding}
            <a onclick="Tygh.$('#discussion').click(); Tygh.$('#opener_new_post a').click();  Tygh.$.scrollToElm(Tygh.$('#new_post_dialog')); return false;">{__("write_review")}</a>
        {/if}
    <!--average_rating_{$object_type}_{$object_id}--></span>
{/if}