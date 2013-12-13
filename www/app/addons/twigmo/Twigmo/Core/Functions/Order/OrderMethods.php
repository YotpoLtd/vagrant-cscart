<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Twigmo\Core\Functions\Order;

use Tygh\Session;

class OrderMethods
{
    final public function getOrderSectionCondition(
        $section_id,
        $sort_by,
        $date_periods,
        $total_periods
    ) {
        $section_condition = ' AND ';

        if ($sort_by == 'date') {

            if ($section_id == 'future') {
                $max_date = max($date_periods);
                $section_condition .= db_quote("?:orders.timestamp > ?i", TIME);

            } elseif ($section_id == 'past') {
                $min_date = min($date_periods);
                $section_condition .= db_quote("?:orders.timestamp <= ?i", $min_date);

            } else {
                $end_date = TIME;
                foreach ($date_periods as $period_id => $start_date) {
                    if ($section_id == $period_id) {
                        $section_condition .= db_quote(
                            "?:orders.timestamp > ?i AND ?:orders.timestamp <= ?i",
                            $start_date,
                            $end_date
                        );
                        break;
                    }
                    $end_date = $start_date;
                }
            }

        } elseif ($sort_by == 'status') {
            $section_condition .= db_quote("?:orders.status = ?s", $section_id);

        } elseif ($sort_by == 'total') {

            if ($section_id == 'less') {
                $min_total = min($total_periods);
                $section_condition .= db_quote("?:orders.total <= ?i", $min_total);

            } else {

                $prev_total = 0;
                foreach ($total_periods as $subtotal) {
                    if ($section_id == 'more_' . $subtotal) {
                        $section_condition .= db_quote("?:orders.total >= ?i", $subtotal);
                        if ($prev_total) {
                            $section_condition .= db_quote(
                                " AND ?:orders.total < ?i",
                                $prev_total
                            );
                        }
                        break;
                    }
                    $prev_total = $subtotal;
                }
            }
        }

        return $section_condition;
    }

    final public function getOrderSectionsInfo(
        $date_periods,
        $total_periods,
        $orders,
        $sort_by,
        $sort_order
    ) {
        $order_sections = array();
        $section_names = array();
        $order_totals = array();

        $show_empty_sections = false;
        if ($sort_by == 'date') {
            $last_date = min($date_periods);

            foreach (array_keys($date_periods) as $period_id) {
                $section_names[$period_id] = self::getOrderPeriodName($period_id);
            }

            foreach ($orders as $order) {
                $selected_period_id = '';
                if ($order['timestamp'] > TIME) {
                    $selected_period_id = 'future';
                } elseif ($order['timestamp'] < $last_date) {
                    $selected_period_id = 'past';

                } else {
                    foreach ($date_periods as $period_id => $start_date) {
                        if ($order['timestamp'] > $start_date) {
                            $selected_period_id = $period_id;
                            break;
                        }
                    }
                }
                if ($selected_period_id != '') {
                    $order_sections[$selected_period_id][] = $order;
                    $order_totals[$selected_period_id] = isset($order_totals[$selected_period_id])?
                        $order_totals[$selected_period_id] + $order['total'] : $order['total'];
                }
            }

            if (isset($order_sections['future'])) {
                $section_names = array(
                    'future' => self::getOrderPeriodName('future')
                ) + $section_names;
            }
            if (isset($order_sections['past'])) {
                $section_names['past'] = self::getOrderPeriodName('more_than_year');
            }

            if ($sort_order == 'asc') {
                $section_names = array_reverse($section_names, true);
            }

            $show_empty_sections = true;

        } elseif ($sort_by == 'status') {
            $section_names = fn_get_statuses(STATUSES_ORDER, true);

            ksort($section_names);
            if ($sort_order == 'desc') {
                $section_names = array_reverse($section_names);
            }

            foreach ($orders as $order) {
                $selected_period_id = $order['status'];
                $order_sections[$selected_period_id][] = $order;
                $order_totals[$selected_period_id] = isset($order_totals[$selected_period_id])?
                    $order_totals[$selected_period_id] + $order['total'] : $order['total'];
            }

            $show_empty_sections = true;

        } elseif ($sort_by == 'total') {
            $min_total = min($total_periods);

            $section_names = array();
            foreach ($total_periods as $subtotal) {
                $section_names['more_' . $subtotal] = __('more_than') . ' ' . fn_format_price($subtotal);
            }
            $section_names['less'] = __('less_than') . ' ' . fn_format_price($min_total);

            reset($total_periods);

            foreach ($orders as $order) {
                if ($order['total'] < $min_total) {
                    $selected_period_id = 'less';
                } else {
                    foreach ($total_periods as $subtotal) {
                        if ($order['total'] > $subtotal) {
                            $selected_period_id = 'more_' . $subtotal;
                            break;
                        }
                    }
                }
                if ($selected_period_id) {
                    $order_sections[$selected_period_id][] = $order;
                    $order_totals[$selected_period_id] =
                        isset($order_totals[$selected_period_id])
                        ? $order_totals[$selected_period_id] + $order['total']
                        : $order['total'];
                }
            }

            if ($sort_order == 'asc') {
                $section_names = array_reverse($section_names);
            }
        }

        return array($order_sections, $section_names, $order_totals, $show_empty_sections);
    }

    final public function getOrderConditions($params)
    {
        $condition = $join = $group = '';
        if (!empty($params['cname'])) {
            $arr = explode(' ', $params['cname']);
            if (sizeof($arr) == 2) {
                $condition .= db_quote(
                    " AND ?:orders.firstname LIKE ?l AND ?:orders.lastname LIKE ?l",
                    "%$arr[0]%",
                    "%$arr[1]%"
                );
            } else {
                $condition .= db_quote(
                    " AND (?:orders.firstname LIKE ?l OR ?:orders.lastname LIKE ?l)",
                    "%$params[cname]%",
                    "%$params[cname]%"
                );
            }
        }
        if (!empty($params['tax_exempt'])) {
            $condition .= db_quote(" AND ?:orders.tax_exempt = ?s", $params['tax_exempt']);
        }
        if (!empty($params['email'])) {
            $condition .= db_quote(" AND ?:orders.email LIKE ?l", "%$params[email]%");
        }
        if (!empty($params['user_id'])) {
            $condition .= db_quote(' AND ?:orders.user_id IN (?n)', $params['user_id']);
        }
        if (!empty($params['total_from'])) {
            $condition .= db_quote(" AND ?:orders.total >= ?d", fn_convert_price($params['total_from']));
        }
        if (!empty($params['total_to'])) {
            $condition .= db_quote(" AND ?:orders.total <= ?d", fn_convert_price($params['total_to']));
        }
        if (!empty($params['status'])) {
            $condition .= db_quote(' AND ?:orders.status IN (?a)', $params['status']);
        }
        if (!empty($params['order_id'])) {
            $multiple_ids = strpos($params['order_id'], ',') !== false;
            $condition .= db_quote(
                ' AND ?:orders.order_id IN (?n)',
                (!is_array($params['order_id']) && $multiple_ids
                    ? explode(',', $params['order_id'])
                    : $params['order_id']
                )
            );
        }
        if (!empty($params['p_ids']) || !empty($params['product_view_id'])) {
            $multiple_ids = strpos($params['p_ids'], ',') !== false;
            $arr =
            ($multiple_ids || !is_array($params['p_ids']))
            ? explode(',', $params['p_ids'])
            : $params['p_ids'];
            if (empty($params['product_view_id'])) {
                $condition .= db_quote(" AND ?:order_details.product_id IN (?n)", $arr);
            } else {
                $condition .= db_quote(
                    " AND ?:order_details.product_id IN (?n)",
                    db_get_fields(
                        fn_get_products(
                            array('view_id' => $params['product_view_id'],
                                'get_query' => true
                            )
                        )
                    )
                );
            }
            $join .= " LEFT JOIN ?:order_details ON ?:order_details.order_id = ?:orders.order_id";
        }
        if (!empty($params['admin_user_id'])) {
            $condition .= db_quote(" AND ?:new_orders.user_id = ?i", $params['admin_user_id']);
            $join .= " LEFT JOIN ?:new_orders ON ?:new_orders.order_id = ?:orders.order_id";
        }
        if (!empty($params['shippings'])) {
            $set_conditions = array();
            foreach ($params['shippings'] as $v) {
                $set_conditions[] = db_quote("FIND_IN_SET(?s, ?:orders.shipping_ids)", $v);
            }
            $condition .= " AND (" . implode(' OR ', $set_conditions) . ")";
        }
        if (!empty($params['period']) && $params['period'] != 'A') {
            list($params['time_from'], $params['time_to']) = fn_create_periods($params);

            $condition .= db_quote(
                " AND (?:orders.timestamp >= ?i AND ?:orders.timestamp <= ?i)",
                $params['time_from'],
                $params['time_to']
            );
        }
        if (!empty($params['custom_files']) && $params['custom_files'] == 'Y') {
            $condition .= db_quote(" AND ?:order_details.extra LIKE ?l", "%custom_files%");

            if (empty($params['p_ids']) && empty($params['product_view_id'])) {
                $join .= " LEFT JOIN ?:order_details ON ?:order_details.order_id = ?:orders.order_id";
            }
        }

        return array($condition, $join);
    }

    final public function orderPlacementRoutines(
        $order_id,
        $force_notification = array(),
        $clear_cart = true,
        $action = ''
    ) {
        // don't show notifications
        // only clear cart
        $order_info = fn_get_order_info($order_id, true);
        $display_notification = true;

        fn_set_hook(
            'placement_routines',
            $order_id,
            $order_info,
            $force_notification,
            $clear_cart,
            $action,
            $display_notification
        );

        if (!empty($_SESSION['cart']['placement_action'])) {
            if (empty($action)) {
                $action = $_SESSION['cart']['placement_action'];
            }
            unset($_SESSION['cart']['placement_action']);
        }

        if (AREA == 'C' && !empty($order_info['user_id'])) {
            $__fake = '';
            fn_save_cart_content($__fake, $order_info['user_id']);
        }

        $edp_data = fn_generate_ekeys_for_edp(array(), $order_info);
        fn_order_notification($order_info, $edp_data, $force_notification);

        // Empty cart
        if ($clear_cart == true && (substr_count('OPT', $order_info['status']) > 0)) {
            $_SESSION['cart'] = array(
                'user_data' => !empty($_SESSION['cart']['user_data'])?
                                $_SESSION['cart']['user_data']:
                                array(),
                'profile_id' => !empty($_SESSION['cart']['profile_id'])?
                                    $_SESSION['cart']['profile_id']:
                                    0,
                'user_id' => !empty($_SESSION['cart']['user_id'])?
                                    $_SESSION['cart']['user_id']:
                                    0,
            );

            db_query(
                'DELETE FROM ?:user_session_products WHERE session_id = ?s AND type = ?s',
                Session::getId(),
                'C'
            );
        }

        $is_twg_hook = true;
        $_error = false;
        fn_set_hook(
            'order_placement_routines',
            $order_id,
            $force_notification,
            $order_info,
            $_error,
            $is_twg_hook
        );
    }

    private static function getOrderPeriodName($period_id)
    {
        return __($period_id);
    }
}
