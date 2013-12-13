{script src="js/lib/daterangepicker/moment.min.js"}
{script src="js/lib/daterangepicker/daterangepicker.js"}
{style src="lib/daterangepicker/daterangepicker.css"}

{$start_date = $start_date|default:("-30 day"|strtotime)}
{$end_date = $end_date|default:("now"|strtotime)}

<div id="{$id}" class="reportrange {$extra_class}" {if $data_url}data-ca-target-url="{$data_url}"{/if} {if $result_ids}data-ca-target-id="{$result_ids}"{/if}>
    <a class="btn-text">
        <span>
            {$start_date|date_format:"%b %d, %Y"} — {$end_date|date_format:"%b %d, %Y"}
        </span>
        <b class="caret"></b>
    </a>
</div>

<script type="text/javascript">
//<![CDATA[
Tygh.$(document).ready(function() {
	moment.lang('{$smarty.const.DEFAULT_LANGUAGE}', {
    	monthsShort : ['{__("month_name_abr_1")}', '{__("month_name_abr_2")}', '{__("month_name_abr_3")}', '{__("month_name_abr_4")}', '{__("month_name_abr_5")}', '{__("month_name_abr_6")}', '{__("month_name_abr_7")}', '{__("month_name_abr_8")}', '{__("month_name_abr_9")}', '{__("month_name_abr_10")}', '{__("month_name_abr_11")}', '{__("month_name_abr_12")}'
    	]
	});
	moment.lang('{$smarty.const.DEFAULT_LANGUAGE}');
	fn_init_daterange_picker('{$id}');
});
//]]>

function fn_init_daterange_picker(id)
{
	Tygh.$('#' + id).daterangepicker({
	    ranges: {
	        '{__("today")}': [moment().startOf('day'), moment().endOf('day')],
	        '{__("yesterday")}': [moment().subtract('days', 1).startOf('day'), moment().subtract('days', 1).endOf('day')],
	        '{__("last_n_days", ["[N]" => 7])}': [moment().startOf('day').subtract('days', 6), moment().endOf('day')],
	        '{__("last_n_days", ["[N]" => 30])}': [moment().startOf('day').subtract('days', 29), moment().endOf('day')],
	        '{__("this_month")}': [moment().startOf('month'), moment().endOf('month')],
	        '{__("last_month")}': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
	    },
	    startDate: moment({$time_from}*1000).startOf('day'),
        endDate: moment({$time_to}*1000).startOf('day'),
        locale: {
            applyLabel: '{__("apply")}',
            clearLabel: '{__("clear")}',
            fromLabel: '{__("from")}',
            toLabel: '{__("to")}',
            customRangeLabel: '{__("custom_range")}',
            monthNames: ['{__("month_name_abr_1")}','{__("month_name_abr_2")}','{__("month_name_abr_3")}','{__("month_name_abr_4")}','{__("month_name_abr_5")}','{__("month_name_abr_6")}','{__("month_name_abr_7")}','{__("month_name_abr_8")}','{__("month_name_abr_9")}','{__("month_name_abr_10")}','{__("month_name_abr_11")}','{__("month_name_abr_12")}'],
            daysOfWeek: ['{__("weekday_abr_0")}', '{__("weekday_abr_1")}', '{__("weekday_abr_2")}', '{__("weekday_abr_3")}', '{__("weekday_abr_4")}', '{__("weekday_abr_5")}', '{__("weekday_abr_6")}']
        },
        format: '{if $settings.Appearance.calendar_date_format == "month_first"}DD/MM/YYYY{else}MM/DD/YYYY{/if}'
	}, 
	function(start, end) {
		var self = Tygh.$(this.element);
		var $ = Tygh.$;

		start = !start ? moment({$time_from}*1000).startOf('day') : start;
		end = !end ? moment({$time_to}*1000).startOf('day') : end;
	    $('span', self).html(start.format('MMM D, YYYY') + ' — ' + end.format('MMM D, YYYY'));
	    if (self.data('ca-target-url') && self.data('ca-target-id')) {
	    	$.ceAjax('request', $.attachToUrl(self.data('ca-target-url'), 'time_from=' + (start.utc() / 1000) + '&time_to=' + (parseInt(end.utc() / 1000) + 86400)), { // Seconds in day
	    			result_ids: self.data('ca-target-id'),
	    			caching: false,
	    			force_exec: true,
	    			callback: function(id) {
	    				Tygh.$('.reportrange').each(function(){
	    					fn_init_daterange_picker(Tygh.$(this).prop('id'));
	    				});
	    				
	    			}
	    		});
	    }
	});
}
</script>