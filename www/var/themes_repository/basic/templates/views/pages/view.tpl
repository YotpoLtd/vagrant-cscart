<div class="wysiwyg-content">
    {hook name="pages:page_content"}
    {$page.description nofilter}
    {/hook}
    {capture name="mainbox_title"}{$page.page}{/capture}
</div>
    
{hook name="pages:page_extra"}
{/hook}