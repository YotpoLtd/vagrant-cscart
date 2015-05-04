{if $layout_data.layout_width != "fixed"}
    {if $parent_grid.width > 0}
        {$fluid_width = fn_get_grid_fluid_width($layout_data.width, $parent_grid.width, $grid.width)}
    {else}
        {$fluid_width = $grid.width}
    {/if}
{/if}



{if $grid.alpha}<div class="{if $layout_data.layout_width != "fixed"}row-fluid {else}row{/if}">{/if}
    <div class="span{$fluid_width|default:$grid.width}{if $grid.offset} offset{$grid.offset}{/if} {$grid.user_class}" >
        {if $grid.status == "A" && $content}
            {$content nofilter}
        {/if}
    </div>
{if $grid.omega}</div>{/if}