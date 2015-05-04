{capture name="mainbox"}
{assign var="show_latest_orders" value="orders"|fn_check_permissions:'manage':'admin'}
{assign var="show_orders" value="sales_reports"|fn_check_permissions:'reports':'admin'}
{assign var="show_inventory" value="products"|fn_check_permissions:'manage':'admin'}
{assign var="show_users" value="profiles"|fn_check_permissions:'manage':'admin'}

<script type="text/javascript">
//<![CDATA[
(function(_, $) {

    _.drawChart = function(is_day) {
        if (typeof google == "undefined") {
            return false;
        }

        function get_data(div) {
            var id = $(div).attr('id');
            var dataTable = new google.visualization.DataTable();
            if (is_day) {
                dataTable.addColumn('timeofday', 'Date');
            } else {
                dataTable.addColumn('date', 'Date');
            }
            dataTable.addColumn('number', '{__("previous_period")}');
            dataTable.addColumn('number', '{__("current_period")}');
            dataTable.addRows(_.chart_data[id]);

            var dataView = new google.visualization.DataView(dataTable);
            dataView.setColumns([0, 1, 2]);

            return dataView;
        }

        var options = {
            chartArea: {
                left: 7,
                top: 10,
                width: 556,
                height: 208
            },
            colors: ['#f491a5','#8fd1ff'],
            tooltip: {
                showColorCode: true
            },
            lineWidth: 4,
            hAxis: {
                baselineColor: '#e1e1e1',
                textStyle: {
                    color: '#a1a1a1',
                    fontSize: 11
                },
                gridlines: {
                    count: 6
                }
            },
            legend: {
                position: 'none'
            },
            pointSize: 10,
            vAxis: {
                minValue: 0,
                baselineColor: '#e1e1e1',
                textPosition: 'in',
                textStyle: {
                    color: '#a1a1a1',
                    fontSize: 11
                },
                gridlines: {
                    count: 10
                }
            }
        };
        if (!is_day) {
            options.hAxis.format = 'MMM d';
        }

        $('.dashboard-statistics-chart:visible').each(function(i, div) {
            var dataView = get_data(div);
            var chart = new google.visualization.AreaChart(div);
            chart.draw(dataView, options);
        });

        $('#statistics_tabs .tabs li').on('click', function() {
            $('.dashboard-statistics-chart:visible').each(function(i, div) {
                var dataView = get_data(div);
                var chart = new google.visualization.AreaChart(div);
                chart.draw(dataView, options);
            });
        });
    }

    $(document).ready(function() {
        $.getScript('//www.google.com/jsapi', function() {
            setTimeout(function() { // do not remove it - otherwise it will be slow in ff
                google.load('visualization', '1.0', {
                    packages: ['corechart'],
                    callback: function() {
                        _.drawChart({$is_day});
                    }
                });
            }, 0);
        });

    });
}(Tygh, Tygh.$));
//]]>
</script>


{hook name="index:index"}
<div class="dashboard" id="dashboard">
    <table class="dashboard-card-table">
        <tbody>
            <tr>
                {hook name="index:finance_statistic"}
                {if !empty($orders_stat.orders)}
                    <td>
                        <div class="dashboard-card">
                            <div class="dashboard-card-title">{__("orders")}</div>
                            <div class="dashboard-card-content">
                                <h3>{$orders_stat.orders|count}</h3>{$orders_stat.prev_orders|count}, {if $orders_stat.diff.orders_count > 0}+{/if}{$orders_stat.diff.orders_count}
                            </div>
                        </div>
                    </td>
                {/if}
                {if !empty($orders_stat.orders_total)}
                    <td>
                        <div class="dashboard-card">
                            <div class="dashboard-card-title">{__("sales")}</div>
                            <div class="dashboard-card-content">
                                <h3>{include file="common/price.tpl" value=$orders_stat.orders_total.totally_paid}</h3>{include file="common/price.tpl" value=$orders_stat.prev_orders_total.totally_paid}, {if $orders_stat.orders_total.totally_paid > $orders_stat.prev_orders_total.totally_paid}+{/if}{$orders_stat.diff.sales nofilter}%
                            </div>
                        </div>
                    </td>
                {/if}
                {if !empty($orders_stat.taxes)}
                    <td>
                        <div class="dashboard-card">
                            <div class="dashboard-card-title">{__("taxes")}</div>
                            <div class="dashboard-card-content">
                                <h3>{include file="common/price.tpl" value=$orders_stat.taxes.subtotal}</h3>{include file="common/price.tpl" value=$orders_stat.taxes.prev_subtotal}, {if $orders_stat.taxes.subtotal > $orders_stat.taxes.prev_subtotal}+{/if}{$orders_stat.taxes.diff nofilter}%
                            </div>
                        </div>
                    </td>
                {/if}
                {if !empty($orders_stat.abandoned_cart_total)}
                    <td>
                        <div class="dashboard-card">
                            <div class="dashboard-card-title">{__("users_carts")}</div>
                            <div class="dashboard-card-content">
                                <h3>{$orders_stat.abandoned_cart_total|default:0}</h3>{$orders_stat.prev_abandoned_cart_total|default:0}, {if $orders_stat.abandoned_cart_total > $orders_stat.prev_abandoned_cart_total}+{/if}{$orders_stat.diff.abandoned_carts nofilter}%
                            </div>
                        </div>
                    </td>
                {/if}
                {/hook}
            </tr>
        </tbody>
    </table>

    {function name="get_orders" limit=5}
        {$params = ['status' => $status, 'time_from' => $time_from, 'time_to' => $time_to, 'period' => 'C']}
        {$orders = $params|fn_get_orders:$limit}

        <table class="table table-middle table-last-td-align-right">
            <tbody>
            {foreach from=$orders.0 item="order"}
                <tr>
                    <td>
                        <span class="label btn-info o-status-{$order.status|lower}">{$order_statuses[$order.status].description}</span>
                    </td>
                    <td><a href="{"orders.details?order_id=`$order.order_id`"|fn_url}">{__("order")} #{$order.order_id}</a> {__("by")} {if $order.user_id}<a href="{"profiles.update?user_id=`$order.user_id`"|fn_url}">{/if}{$order.lastname} {$order.firstname}{if $order.user_id}</a>{/if}</td>
                    <td><span class="date">{$order.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span></td>
                    <td><h4>{include file="common/price.tpl" value=$order.total}</h4></td>
                </tr>
            {foreachelse}
                <tr><td>{__("no_data")}</td></tr>
            {/foreach}
            </tbody>
        </table>
    {/function}

    <div class="dashboard-row">
        {if !empty($order_statuses)}
            <div class="dashboard-recent-orders cm-j-tabs tabs" data-ca-width="500">
                <h4>{__("recent_orders")}</h4>
                <ul class="nav nav-pills">
                    <li id="tab_recent_all" class="active cm-js"><a href="#status_all" data-toggle="tab">All</a></li>
                    {foreach from=$order_statuses item="status"}
                        <li id="tab_recent_{$status.status}" class="cm-js"><a href="#status_{$status.status}" data-toggle="tab">{$status.description}</a></li>
                    {/foreach}
                </ul>

                <div class="tab-content cm-tabs-content">
                    <div class="tab-pane" id="content_tab_recent_all">
                        {get_orders status=""}
                    </div>
                    {foreach from=$order_statuses item="status"}
                        <div class="tab-pane" id="content_tab_recent_{$status.status}">
                            {get_orders status=$status.status}
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
        {if !empty($graphs)}
            <div class="dashboard-statistics">
                <h4>
                    {__("statistics")}
                </h4>
                 {capture name="chart_tabs"}
                <div id="content_sales_chart">
                    <div id="dashboard_statistics_sales_chart" class="dashboard-statistics-chart spinner">
                    </div>
                </div>
                {hook name="index:chart_statistic"}
                {/hook}
                {/capture}

                <div id="statistics_tabs">
                    {include file="common/tabsbox.tpl" content=$smarty.capture.chart_tabs}
                    <script>
                        Tygh.chart_data = {
                            {foreach from=$graphs item="graph" key="chart" name="graphs"}
                                '{$chart}': [
                                    {foreach from=$graph item="data" key="date" name="graph"}
                                        [{if $is_day}[{$date}, 0, 0, 0]{else}new Date({$date}){/if}, {$data.prev}, {$data.cur}]{if !$smarty.foreach.graph.last},{/if}
                                    {/foreach}
                                ]{if !$smarty.foreach.graphs.last},{/if}
                            {/foreach}
                        };
                        Tygh.drawChart({$is_day});
                    </script>
                <!--statistics_tabs--></div>
            </div>
        {/if}
    </div>

    <div class="dashboard-row-bottom">
        <div class="dashboard-tables">
            <table class="dashboard-card-table dashboard-card-table-center nowrap">
                <tbody>
                    <tr>
                        {if !empty($general_stats.products)}
                            <td>
                                <div class="dashboard-card">
                                    <div class="dashboard-card-title">{__("active_products")}</div>
                                    <div class="dashboard-card-content">
                                        <h3><a href="{"products.manage?status=A"|fn_url}">{$general_stats.products.total_products|number_format}</a></h3>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="dashboard-card">
                                    <div class="dashboard-card-title">{__("out_of_stock_products")}</div>
                                    <div class="dashboard-card-content">
                                        <h3><a href="{"products.manage?amount_from=&amount_to=0&tracking[0]=B&tracking[1]=O"|fn_url}">{$general_stats.products.out_of_stock_products|number_format}</a></h3>
                                    </div>
                                </div>
                            </td>
                        {/if}
                        {if !empty($general_stats.customers)}
                            <td>
                                <div class="dashboard-card">
                                    <div class="dashboard-card-title">{__("registered_customers")}</div>
                                    <div class="dashboard-card-content">
                                        <h3><a href="{"profiles.manage?user_type=C"|fn_url}">{$general_stats.customers.registered_customers|number_format}</a></h3>
                                    </div>
                                </div>
                            </td>
                        {/if}
                        {if !empty($general_stats.categories)}
                            <td>
                                <div class="dashboard-card">
                                    <div class="dashboard-card-title">{__("categories")}</div>
                                    <div class="dashboard-card-content">
                                        <h3><a href="{"categories.manage"|fn_url}">{$general_stats.categories.total_categories|number_format}</a></h3>
                                    </div>
                                </div>
                            </td>
                        {/if}
                        {if !empty($general_stats.companies)}
                            <td>
                                <div class="dashboard-card">
                                    <div class="dashboard-card-title">{__("vendors")}</div>
                                    <div class="dashboard-card-content">
                                        <h3><a href="{"companies.manage"|fn_url}">{$general_stats.companies.total_companies|number_format}</a></h3>
                                    </div>
                                </div>
                            </td>
                        {/if}
                        {if !empty($general_stats.pages)}
                            <td>
                                <div class="dashboard-card">
                                    <div class="dashboard-card-title">{__("web_pages")}</div>
                                    <div class="dashboard-card-content">
                                        <h3><a href="{"pages.manage"|fn_url}">{$general_stats.pages.total_pages|number_format}</a></h3>
                                    </div>
                                </div>
                            </td>
                        {/if}
                    </tr>
                </tbody>
            </table>

            {hook name="index:order_statistic"}
            {/hook}
            {if "orders.manage"|fn_check_view_permissions:"GET"}
                <div class="dashboard-table dashboard-table-order-by-statuses">
                    <h4>{__("order_by_status")}</h4>
                     <div class="table-wrap" id="dashboard_order_by_status">
                        <table class="table">
                            <thead>
                            <tr>
                                <th width="25%">{__("status")}</th>
                                <th width="25%">{__("qty")}</th>
                                <th width="25%">{__(total)}</th>
                                <th width="25%">{__("shipping")}</th>
                            </tr>
                            </thead>
                        </table>
                        <div class="scrollable-table">
                        <table class="table table-striped">
                            <tbody>
                                {foreach from=$order_by_statuses item="order_status"}
                                    {$url = "orders.manage?is_search=Y&period=C&time_from=`$time_from`&time_to=`$time_to`&status[]=`$order_status.status`"|fn_url}
                                    <tr>
                                        <td width="25%"><a href="{$url}">{$order_status.status_name}</a></td>
                                        <td width="25%">{$order_status.count}</td>
                                        <td width="25%">{include file="common/price.tpl" value=$order_status.total}</td>
                                        <td width="25%">{include file="common/price.tpl" value=$order_status.shipping}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                        </div>
                    <!--dashboard_order_by_status--></div>
                </div>
            {/if}
        </div>

        {if "logs.manage"|fn_check_view_permissions:"GET"}
            <div class="dashboard-activity">
                <div class="pull-right"><a href="{"logs.manage"|fn_url}">{__('show_all')}</a></div>
                <h4>{__("recent_activity")}</h4>
                {function name="show_log_row" item=[]}
                    {if $item}
                        <div class="item">
                            {hook name="index:recent_activity"}
                                {$_type = "log_type_`$item.type`"}
                                {$_action = "log_action_`$item.action`"}

                                {__($_type)}{if $item.action}&nbsp;({__($_action)}){/if}:

                                {if $item.type == "users" && "profiles.update?user_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
                                    {if $item.content.id}<a href="{"profiles.update?user_id=`$item.content.id`"|fn_url}">{/if}{$item.content.user}{if $item.content.id}</a>{/if}<br>
                                    
                                {elseif $item.type == "orders" && "orders.details?order_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
                                    {$item.content.status}<br>
                                    <a href="{"orders.details?order_id=`$item.content.id`"|fn_url}">{__("order")}&nbsp;{$item.content.order}</a><br>
                                {elseif $item.type == "products" && "products.update?product_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
                                    <a href="{"products.update?product_id=`$item.content.id`"|fn_url}">{$item.content.product}</a><br>

                                {elseif $item.type == "categories" && "categories.update?category_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
                                    <a href="{"categories.update?category_id=`$item.content.id`"|fn_url}">{$item.content.category}</a><br>                        
                                {/if}

                                {hook name="index:recent_activity_item"}{/hook}

                                <span class="date">{$item.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span>
                            {/hook}
                        </div>
                    {/if}
                {/function}

                <div class="dashboard-activity-list">
                    {foreach from=$logs item="item"}
                        {show_log_row item=$item}
                    {/foreach}
                </div>
            </div>
        {/if}
    </div>
<!--dashboard--></div>
{/hook}

{capture name="buttons"}
        {include file="common/daterange_picker.tpl" id="dashboard_date_picker" extra_class="pull-right offset1" data_url="index.index"|fn_url result_ids="dashboard" start_date=$time_from end_date=$time_to}
{/capture}
{/capture}
{include file="common/mainbox.tpl" no_sidebar=true buttons=$smarty.capture.buttons title=__("dashboard") content=$smarty.capture.mainbox tools=$smarty.capture.tools}

{if $show_welcome}
    <div class="hidden cm-dialog-auto-open cm-dialog-auto-size" title="{__("installer_complete_title")}" id="after_install_dialog">
        {assign var="company_1" value="1"|fn_get_company_data}
        {assign var="company_2" value="2"|fn_get_company_data}

        <div class="store-mode welcome-screen">
        {if "ULTIMATE"|fn_allowed_for}
        <p>
            {if $company_2}
                {__("installer_complete_storefronts_description")}:
            {else}
                {__("installer_complete_storefront_description")}:
            {/if}
        </p>
        <p>
            {$company_1.company}: <a href="http://{$company_1.storefront}" target="_blank">http://{$company_1.storefront|unpuny}</a><br>
            {if $company_2}
                {$company_2.company}: <a href="http://{$company_2.storefront}" target="_blank">http://{$company_2.storefront|unpuny}</a><br>
            {/if}
        </p>
        {/if}

        {if "MULTIVENDOR"|fn_allowed_for}
        <p>{__("installer_complete_storefront_description")}:</p>
        <p>
            {$settings.Company.company_name}: <a href="{$config.http_location|fn_url}">{$config.http_location|fn_url}</a><br>
        </p>
        {/if}

        <p>
            {capture name="wizard_link"}
                <a class="cm-dialog-opener cm-ajax btn btn-primary" data-ca-target-id="content_settings_wizard" title="{__("settings_wizard")}" href="{"settings_wizard.view?return_url=`$config.current_url`"|fn_url}">{__("go_to_settings_wizard")}</a>
            {/capture}

            {__("installer_complete_storefronts_configuring")}
        </p>

        <div class="well">
            {$user_data = $auth.user_id|fn_get_user_info}
            {__("installer_complete_administrator_info", ['[email]' => $user_data.email])}
        </div>

        <p>
            {__("installer_complete_thank_you", ['[product_name]' => $product_name])}
        </p>

        {include file="common/share.tpl"}

        </div>

        <div class="buttons-container">
            <input type="button" value="{__("close")}" class="btn cm-dialog-closer">
            {$smarty.capture.wizard_link nofilter}
        </div>
    </div>
{/if}