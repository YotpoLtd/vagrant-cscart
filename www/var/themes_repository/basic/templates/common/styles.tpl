{styles use_scheme=true}
{hook name="index:styles"}
    {style src="reset.css"}
    {style src="grid.less"}
    {style src="lib/ui/jqueryui.css"}
    {style src="base.css"}
    {style src="glyphs.css"}
    {style src="styles.css"}
    {style src="print.css" media="print"}

    {if $runtime.customization_mode.translation || $runtime.customization_mode.design}
    {style src="design_mode.css"}
    {/if}
    {if $include_dropdown}
    {style src="dropdown.css"}
    {/if}

    {style src="scheme.less"}
{/hook}
{/styles}