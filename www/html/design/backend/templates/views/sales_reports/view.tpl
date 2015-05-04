{script src="js/lib/amcharts/swfobject.js"}

{capture name="mainbox"}
<div id="content_{$report.report_id}">
    {if $report}

{capture name="tabsbox"}
{if $report.tables}
{assign var="table_id" value=$table.table_id}
{assign var="table_prefix" value="table_$table_id"}
<div id="content_table_{$table_id}">

{if !$table.elements || $table.empty_values == "Y"}

<p>{__("no_data")}</p>

{elseif $table.type == "T"}

{if $table_conditions.$table_id}
    {include file="common/subheader.tpl" title=__("table_conditions") meta="collapsed" target="#box_table_conditions_`$table_id`"}
    <div id="box_table_conditions_{$table_id}" class="collapse">
        <dl class="dl-horizontal">
        {foreach from=$table_conditions.$table_id item="i"}
            <dt>{$i.name}:</dt>
            <dd>
                {foreach from=$i.objects item="o" name="feco"}
                    {if $o.href}<a href="{$o.href|fn_url}">{/if}{$o.name}{if $o.href}</a>{/if}{if !$smarty.foreach.feco.last}, {/if}
                {/foreach}
            </dd>
        {/foreach}
        </dl>
    </div>
{/if}

{if $table.interval_id != 1}
    <table width="100%">
        <tr valign="top">
            {cycle values="" assign=""}
            <td width="30%">
                <table width="100%" class="table">
                    <thead>
                        <tr>
                            <th width="100%">{$table.parameter}</th>
                        </tr>
                    </thead>
                    {foreach from=$table.elements item=element}
                        <tr>
                            <td>{$element.description nofilter}&nbsp;</td>
                        </tr>
                    {/foreach}
                    <tr class="td-no-bg">
                        <td class="right">{__("total")}:</td>
                    </tr>
                </table>
            </td>
            <td width="70%">
                {cycle values="" assign=""}
                <div id="div_scroll_{$table_id}" class="scroll-x scroll-sales-report">
                    <table class="table no-left-border" >
                        <thead>
                            <tr class="nowrap">
                                {foreach from=$table.intervals item=row}
                                    <th class="center">&nbsp;{$row.description}&nbsp;</th>
                                {/foreach}
                            </tr>
                        </thead>
                        {foreach from=$table.elements item=element}
                            <tr>
                                {assign var="element_hash" value=$element.element_hash}
                                {foreach from=$table.intervals item=row}
                                    {assign var="interval_id" value=$row.interval_id}
                                    <td class="center">
                                        {if $table.values.$element_hash.$interval_id}
                                            {if $table.display != "product_number" && $table.display != "order_number"}{include file="common/price.tpl" value=$table.values.$element_hash.$interval_id}{else}{$table.values.$element_hash.$interval_id}{/if}
                                            {else}-{/if}</td>
                                {/foreach}
                            </tr>
                        {/foreach}
                        <tr class="td-no-bg">
                            {foreach from=$table.totals item=row}
                                <td class="center">
                                    {if $row}
                                        <span>{if $table.display != "product_number" && $table.display != "order_number"}{include file="common/price.tpl" value=$row}{else}{$row}{/if}</span>
                                        {else}-{/if}
                                </td>
                            {/foreach}
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
{else}

<table class="table table-middle">
    <thead>
        <tr>
            <th>{$table.parameter}</th>
            {foreach from=$table.intervals item=row}
                {assign var="interval_id" value=$row.interval_id}
                {assign var="interval_name" value="reports_interval_$interval_id"}
                <th class="right">{__($interval_name)}</th>
            {/foreach}
        </tr>
    </thead>
    <tbody>
        {assign var="elements_count" value=$table.elements|sizeof}
        {foreach from=$table.elements item=element}
            {assign var="element_hash" value=$element.element_hash}
            <tr>
                {foreach from=$table.intervals item=row}
                    {assign var="interval_id" value=$row.interval_id}
                    {math equation="round(value_/max_value*100)" value_=$table.values.$element_hash.$interval_id|default:"0" max_value=$table.max_value assign="percent_value"}
                    <td>
                        {$element.description nofilter}&nbsp;
                        {include file="views/sales_reports/components/graph_bar.tpl" bar_width="100px" value_width=$percent_value}
                    </td>
                    <td  class="right">
                        {if $table.values.$element_hash.$interval_id}
                            {if $table.display != "product_number" && $table.display != "order_number"}
                                {include file="common/price.tpl" value=$table.values.$element_hash.$interval_id}{else}{$table.values.$element_hash.$interval_id}
                            {/if}
                        {else}
                            -
                        {/if}
                    </td>
                {/foreach}
            </tr>
        {/foreach}
        <tr class="td-no-bg">
            <td class="right" width="70%">{__("total")}:</td>
            <td class="right" width="30%">
                {foreach from=$table.totals item="row"}
                    {if $row}
                        {if $table.display != "product_number" && $table.display != "order_number"}
                            {include file="common/price.tpl" value=$row}
                        {else}
                            {$row}
                        {/if}
                    {else}
                        -
                    {/if}
                {/foreach}
            </td>
        </tr>
    </tbody>
</table>

{/if}

{elseif $table.type == "P"}
    <div id="{$table_prefix}pie">{include file="views/sales_reports/components/amchart.tpl" type="pie" chart_data=$new_array.pie_data chart_id=$table_prefix chart_title=$table.description chart_height=$new_array.pie_height}<!--{$table_prefix}pie--></div>

{elseif $table.type == "C"}
    <div id="{$table_prefix}pie">{include file="views/sales_reports/components/amchart.tpl" type="pie" set_type="piefl" chart_data=$new_array.pie_data chart_id=$table_prefix chart_title=$table.description chart_height=$new_array.pie_height}<!--{$table_prefix}pie--></div>

{elseif $table.type == "B"}
    <div id="div_scroll_{$table_id}" class="reports-graph-scroll">
        <div id="{$table_prefix}bar">{include file="views/sales_reports/components/amchart.tpl" type="column" chart_data=$new_array.column_data chart_id=$table_prefix chart_title=$table.description chart_height=$new_array.column_height chart_width=$new_array.column_width}<!--{$table_prefix}bar--></div>
    </div>
{/if}

<!--content_table_{$table_id}--></div>

{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab="table_`$table_id`" track=true}

{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

<!--content_{$report.report_id}--></div>
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("manage_reports") href="sales_reports.manage"}</li>
        <li>{btn type="list" text=__("edit_report") href="sales_reports.update_table?report_id=$report_id&table_id=`$table.table_id`"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{capture name="sidebar"}
    {include file="views/sales_reports/components/sales_reports_search_form.tpl" period=$report.period search=$report}
{/capture}

{include file="common/mainbox.tpl" title=__("reports") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}