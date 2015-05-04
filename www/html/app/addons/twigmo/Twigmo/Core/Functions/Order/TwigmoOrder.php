<?php

namespace Twigmo\Core\Functions\Order;

use Twigmo\Core\Api;
use Twigmo\Core\Functions\Order\OrderMethods;
use Tygh\Registry;

class TwigmoOrder
{
    public static function apiUpdateOrder($order, $response)
    {
        if (!defined('ORDER_MANAGEMENT')) {
            define('ORDER_MANAGEMENT', true);
        }

        if (!empty($order['status'])) {

            $statuses = fn_get_statuses(STATUSES_ORDER, false, true);

            if (!isset($statuses[$order['status']])) {
                $response->addError(
                    'ERROR_OBJECT_UPDATE',
                    str_replace(
                        '[object]',
                        'orders',
                        __('twgadmin_wrong_api_object_data')
                    )
                );
            } else {
                fn_change_order_status($order['order_id'], $order['status']);
            }
        }

        $cart = array();
        fn_clear_cart($cart, true);
        $customer_auth = fn_fill_auth(array(), array(), false, 'C');

        fn_form_cart($order['order_id'], $cart, $customer_auth);
        $cart['order_id'] = $order['order_id'];

        // update only profile data
        $profile_data = fn_check_table_fields($order, 'user_profiles');

        $cart['user_data'] = fn_array_merge($cart['user_data'], $profile_data);
        fn_calculate_cart_content($cart, $customer_auth, 'A', true, 'I');

        if (!empty($order['details'])) {
            db_query(
                'UPDATE ?:orders SET details = ?s WHERE order_id = ?i',
                $order['details'],
                $order['order_id']
            );
        }

        if (!empty($order['notes'])) {
            $cart['notes'] = $order['notes'];
        }

        list($order_id, $process_payment) = fn_place_order($cart, $customer_auth, 'save');

        return array($order_id, $process_payment);
    }

    public static function apiPlaceOrder($data, &$response, $lang_code = CART_LANGUAGE)
    {
        $cart = & $_SESSION['cart'];
        $auth = & $_SESSION['auth'];
        $orderMethods = new OrderMethods();
        if (empty($cart)) {
            $response->addError(
                'ERROR_ACCESS_DENIED',
                __(
                    'access_denied',
                    $lang_code
                )
            );
            $response->returnResponse();
        }
        if (!empty($data['user'])) {
            fn_twg_api_set_cart_user_data(
                $data['user'],
                $response,
                $lang_code
            );
        }
        if (empty($auth['user_id']) && empty($cart['user_data'])) {
            $response->addError(
                'ERROR_ACCESS_DENIED',
                __(
                    'access_denied',
                    $lang_code
                )
            );
            $response->returnResponse();
        }
        if (empty($data['payment_info']) && !empty($cart['extra_payment_info'])) {
            $data['payment_info'] = $cart['extra_payment_info'];
        }
        if (!empty($data['payment_info'])) {
            $cart['payment_id'] = (int) $data['payment_info']['payment_id'];
            unset($data['payment_info']['payment_id']);

            if (!empty($data['payment_info'])) {
                $cart['payment_info'] = $data['payment_info'];
            }

            unset($cart['payment_updated']);
            fn_update_payment_surcharge($cart, $auth);

            fn_save_cart_content($cart, $auth['user_id']);
        }
        unset($cart['payment_info']['secure_card_number']);

        if (!empty($data['shippings'])) {
                if (!fn_checkout_update_shipping($cart, $data['shippings'])) {
                    unset($cart['shipping']);
                }
        }
        list (,$_SESSION['shipping_rates']) = fn_calculate_cart_content(
            $cart,
            $auth,
            'E'
        );
        if (empty($cart['shipping']) && $cart['shipping_failed']) {
            $response->addError(
                'ERROR_WRONG_CHECKOUT_DATA',
                __(
                    'wrong_shipping_info',
                    $lang_code
                )
            );
            $response->returnResponse();
        }
        if (empty($cart['payment_info']) && !isset($cart['payment_id'])) {
            $response->addError(
                'ERROR_WRONG_CHECKOUT_DATA',
                __(
                    'wrong_payment_info',
                    $lang_code
                )
            );
            $response->returnResponse();
        }
        if (!empty($data['notes'])) {
            $cart['notes'] = $data['notes'];
        }
        $cart['details'] = __('twgadmin_order_via_twigmo');
        list($order_id, $process_payment) = fn_place_order($cart, $auth);
        if (empty($order_id)) {
            return false;
        }
        if ($process_payment == true) {
            $payment_info =
                !empty($cart['payment_info']) ?
                    $cart['payment_info'] :
                    array();
            fn_twg_start_payment($order_id, array(), $payment_info);
        }
        $orderMethods->orderPlacementRoutines($order_id);

        return $order_id;
    }

    public static function apiGetOrderDetails($order_id)
    {
        $order_info = fn_get_order_info($order_id);
        if (empty($order_info) || empty($order_info['order_id'])) {
            return false;
        }

        if (!empty($order_info['items'])) {
            $order_info['products'] = array();

            foreach ($order_info['items'] as $product) {
                $order_info['products'][] = $product;
            }
            unset($order_info['items']);
        }

        $status_info = fn_get_status_data(
            $order_info['status'],
            STATUSES_ORDER,
            $order_info['order_id'],
            CART_LANGUAGE
        );
        if (!empty($status_info['description'])) {
            $order_info['status'] = $status_info['description'];
        }

        if (isset($order_info['products']) && !empty($order_info['products'])) {
            $edp_order_data = fn_get_user_edp(
                $order_info['user_id'],
                $order_info['order_id']
            );
            foreach ($order_info['products'] as $k => $product) {
                $order_info['products'][$k]['extra'] =
                    isset($product['extra']) ?
                        $product['extra'] :
                        array();
                if (
                    isset($product['extra']['is_edp'])
                    && $product['extra']['is_edp'] == 'Y'
                ) {
                    foreach ($edp_order_data as $_product) {
                       if ($_product['product_id'] == $product['product_id']) {
                         $order_info['products'][$k]['extra']['files'] = $_product['files'];
                         $order_info['products'][$k]['files'] = $_product['files'];
                       }
                    }
                }
            }
        }

        return Api::getAsApiObject('orders', $order_info);
    }

    public static function getOrdersAsApiList($orders, $lang_code)
    {
        $order_ids = array();
        foreach ($orders as $order) {
            $order_ids[] = $order['order_id'];
        }

        if (!empty($order_ids)) {
            $payment_names = db_get_hash_array(
                "SELECT order_id, payment
                 FROM ?:orders, ?:payment_descriptions
                 WHERE ?:payment_descriptions.payment_id = ?:orders.payment_id
                 AND ?:payment_descriptions.lang_code = ?s
                 AND ?:orders.order_id IN (?a)",
                'order_id',
                $lang_code,
                $order_ids
            );
            $shippings  = db_get_hash_array(
                "SELECT order_id, data
                 FROM ?:order_data
                 WHERE type = ?s
                 AND order_id IN (?a)",
                'order_id',
                'L',
                $order_ids
            );
        } else {
            $payment_names = array();
            $shippings = array();
        }

        foreach ($orders as $k => $v) {
            $orders[$k]['payment'] = !empty($payment_names[$v['order_id']]['payment'])?
                $payment_names[$v['order_id']]['payment'] : '';
            $orders[$k]['shippings'] = array();
            if (!empty($shippings[$v['order_id']]['data'])) {
                $shippings = unserialize($shippings[$v['order_id']]['data']);

                if (empty($shippings)) {
                    continue;
                }

                foreach ($shippings as $shipping) {
                    $orders[$k]['shippings'][] = array (
                        'carrier' => !empty($shipping['carrier']) ? $shipping['carrier'] : '',
                        'shipping' => !empty($shipping['shipping']) ? $shipping['shipping'] : '',
                    );
                }
            }
        }

        $fields = array (
            'order_id',
            'user_id',
            'total',
            'timestamp',
            'status',
            'firstname',
            'lastname',
            'email',
            'payment_name',
            'shippings'
        );

        return Api::getAsList('orders', $orders, $fields);
    }

    /**
     * Get order data
     */
    public static function getOrderInfo($order_id)
    {
        $object = fn_get_order_info($order_id, false, true, true);
        $object['date'] = strftime(
            Registry::get('settings.Appearance.date_format')
                . ', '
                . Registry::get('settings.Appearance.time_format'),
            $object['timestamp']
        );
        $status_data = fn_get_status_data($object['status'], STATUSES_ORDER);
        $object['status'] =
            empty($status_data['description']) ?
                '' :
                $status_data['description'];

        $object['items'] = !empty($object['items']) && is_array($object['items']) ? array_values($object['items']) : array();

        $object['shipping'] =
            array_values(
                isset($object['shipping']) ? $object['shipping'] : array()
            );
        $object['taxes'] = array_values($object['taxes']);
        $object['items'] = array_values($object['products']);
        unset($object['products']);

        return $object;
    }

    /**
     * Return order/orders info after the order placing
     * @param int   $order_id
     * @param array $response
     */
    public static function returnPlacedOrders(
        $order_id,
        &$response,
        $items_per_page,
        $lang_code
    ) {
        $order = self::getOrderInfo($order_id);

        $_error = false;

        $status = db_get_field('SELECT status FROM ?:orders WHERE order_id=?i', $order_id);

        if ($status == STATUS_PARENT_ORDER) {
            $child_orders = db_get_hash_single_array(
                "SELECT order_id, status
                 FROM ?:orders
                 WHERE parent_order_id = ?i",
                array('order_id', 'status'),
                $order_id
            );
            $status = reset($child_orders);
            $order['child_orders'] = array_keys($child_orders);
        }

        if (!substr_count('OP', $status) > 0) {
            $_error = true;
            if ($status != 'B') {
                if (!empty($child_orders)) {
                    array_unshift($child_orders, $order_id);
                } else {
                    $child_orders = array();
                    $child_orders[] = $order_id;
                }
                $order_id_field = $status == 'N' ? 'processed_order_id' : 'failed_order_id';
                $_SESSION['cart'][$order_id_field] = $child_orders;

                $cart = &$_SESSION['cart'];
                if (!empty($cart['failed_order_id'])) {
                    $_ids =
                        !empty($cart['failed_order_id']) ?
                            $cart['failed_order_id'] :
                            $cart['processed_order_id'];
                    $_order_id = reset($_ids);
                    $_payment_info = db_get_field(
                        "SELECT data
                         FROM ?:order_data
                         WHERE order_id = ?i AND type = 'P'",
                        $_order_id
                    );
                    if (!empty($_payment_info)) {
                        $_payment_info = unserialize(fn_decrypt_text($_payment_info));
                    }
                    $_msg =
                        !empty($_payment_info['reason_text']) ?
                            $_payment_info['reason_text'] :
                            '';
                    $_msg .=
                        empty($_msg) ?
                            __('text_order_placed_error') :
                            '';
                    $response->addError(
                        'ERROR_FAIL_POST_ORDER',
                        $_msg
                    );
                    $cart['processed_order_id'] = $cart['failed_order_id'];
                    unset($cart['failed_order_id']);
                } elseif (
                    !fn_twg_set_internal_errors(
                        $response,
                        'ERROR_FAIL_POST_ORDER'
                    )
                ) {
                    $response->addError(
                        'ERROR_FAIL_POST_ORDER',
                        __('fail_post_order', $lang_code)
                    );
                }
            } else {
                if (!fn_twg_set_internal_errors($response, 'ERROR_ORDER_BACKORDERED')) {
                    $response->addError(
                        'ERROR_ORDER_BACKORDERED',
                        __('text_order_backordered', $lang_code)
                    );
                }
            }
            $response->returnResponse();
        }

        if (empty($order['child_orders'])) {
            $response->setData($order);
        } else {
            $params = array();
            if (empty($_SESSION['auth']['user_id'])) {
                $params['order_id'] = $_SESSION['auth']['order_ids'];
            } else {
                $params['user_id'] = $_SESSION['auth']['user_id'];
            }
            list($orders,,$totals) = fn_get_orders(
                $params,
                $items_per_page,
                true
            );
            $response->setMeta(
                !empty($totals['gross_total']) ? $totals['gross_total'] : 0,
                'gross_total'
            );
            $response->setMeta(
                !empty($totals['totally_paid']) ? $totals['totally_paid'] : 0,
                'totally_paid'
            );
            $response->setMeta($order, 'order');
            $response->setResponseList(
                TwigmoOrder::getOrdersAsApiList($orders, $lang_code)
            );
            $pagination_params = array(
                'items_per_page' => !empty($items_per_page)? $items_per_page : TWG_RESPONSE_ITEMS_LIMIT,
                'page' => !empty($_REQUEST['page'])? $_REQUEST['page'] : 1
            );
            fn_twg_set_response_pagination($response, $pagination_params);
        }
    }

    /**
     * Check if a user have an access to an order
     * @param array $response
     * @param array $auth
     */
    public static function checkIfOrderAllowed($order_id, &$_auth, &$response)
    {
        $allow = true;
        // If user is not logged in and trying to see the order, redirect him to login form
        if (empty($_auth['user_id']) && empty($_auth['order_ids'])) {
            $response->addError('ERROR_ACCESS_DENIED', __('access_denied'));
            $response->returnResponse();
            $allow = false;
        }

        $allowed_id = 0;

        if (!empty($_auth['user_id'])) {
            $allowed_id = db_get_field(
                "SELECT user_id
                 FROM ?:orders
                 WHERE user_id = ?i AND order_id = ?i",
                $_auth['user_id'],
                $order_id
            );
        } elseif (!empty($_auth['order_ids'])) {
            $allowed_id = in_array($order_id, $_auth['order_ids']);
        }

        // Check order status (incompleted order)
        if (!empty($allowed_id)) {
            $status = db_get_field(
                'SELECT status
                 FROM ?:orders
                 WHERE order_id = ?i',
                $order_id
            );
            if ($status == STATUS_INCOMPLETED_ORDER) {
                $allowed_id = 0;
            }
        }
        fn_set_hook('is_order_allowed', $order_id, $allowed_id);

        if (empty($allowed_id)) { // Access denied
            $response->addError(
                'ERROR_ACCESS_DENIED',
                __('access_denied')
            );
            $response->returnResponse();
            $allow = false;
        }

        return $allow;
    }

    public static function getOrderSections($orders, $params)
    {
        // the order periods
        // name points to start period time

        $params['get_conditions'] = 'Y';
        $orderMethods = new OrderMethods();
        list($condition, $join) = $orderMethods->getOrderConditions($params);

        $today = getdate(TIME);
        $wday = empty($today['wday']) ? "6" : (($today['wday'] == 1) ? "0" : $today['wday'] - 1);
        $wstart = getdate(strtotime("-$wday day"));

        $date_periods = array(
            'today' => mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']),
            'week' => mktime(0, 0, 0, $wstart['mon'], $wstart['mday'], $wstart['year']),
            'month' => mktime(0, 0, 0, $today['mon'], 1, $today['year']),
            'year' => mktime(0, 0, 0, 1, 1, $today['year'])
        );

        $total_periods = array(10000, 1000, 100, 10);
        $order_totals = array();

        $sort_order = $params['sort_order'] == 'asc' ? 'asc' : 'desc';
        $sort_by = $params['sort_by'];
        list($order_sections, $section_names, $order_totals, $show_empty_sections) =
            $orderMethods->getOrderSectionsInfo(
                    $date_periods,
                    $total_periods,
                    $orders,
                    $sort_by,
                    $sort_order
        );

        // remove empty sections from the begin and the end of the page

        $pagination = Registry::get('view')->getTemplateVars('pagination');

        $first_section = false;
        $last_section = false;

        if ($pagination['current_page'] == 1) {
            $first_section = true;
        }

        $total_items = 0;
        $first_calculated = false;
        $last_calculated = false;

        foreach (array_keys($section_names) as $section_id) {
            if (!$show_empty_sections) {
                if (!isset($order_sections[$section_id]) || empty($order_sections[$section_id])) {
                    unset($section_names[$section_id]);
                }
                continue;
            }
            if ($pagination['total_pages'] == 1) {
                continue;
            }
            if (isset($order_sections[$section_id]) && !empty($order_sections[$section_id])) {
                $total_items += count($order_sections[$section_id]);
                if (($total_items == $pagination['items_per_page']) &&
                    ($pagination['current_page'] != $pagination['total_pages'])) {

                    $last_section = true;
                    $section_condition = $orderMethods->getOrderSectionCondition(
                        $section_id,
                        $params['sort_by'],
                        $date_periods,
                        $total_periods
                    );

                    $new_totals = db_get_field(
                        "SELECT sum(total) FROM ?:orders $join WHERE 1 $condition $section_condition"
                    );

                    if ($new_totals != $order_totals[$section_id]) {
                        $order_totals[$section_id] = $new_totals;
                        $last_calculated = true;

                    }

                }
                if (!$first_calculated) {
                    $first_calculated = true;
                    if ($pagination['current_page'] > 1) {
                        $section_condition = $orderMethods->getOrderSectionCondition(
                            $section_id,
                            $params['sort_by'],
                            $date_periods,
                            $total_periods
                        );

                        $order_totals[$section_id] = db_get_field(
                            "SELECT sum(total) FROM ?:orders $join WHERE 1 $condition $section_condition"
                        );
                    }
                }
                $first_section = true;
            } elseif ($last_section || !$first_section) {
                if (!$first_section) {
                    unset($section_names[$section_id]);
                }
                if ($last_section) {
                    if ($last_calculated) {
                        unset($section_names[$section_id]);
                    } else {
                        $section_condition = $orderMethods->getOrderSectionCondition(
                            $section_id,
                            $params['sort_by'],
                            $date_periods,
                            $total_periods
                        );

                        $section_total = db_get_field(
                            "SELECT sum(total) FROM ?:orders $join WHERE 1 $condition $section_condition"
                        );

                        if ($section_total > 0) {
                            unset($section_names[$section_id]);
                            $last_calculated = true;
                        }
                    }
                }
            }
        }

        $sections = array();

        foreach ($section_names as $section_id => $section_name) {
            $sections[] = array (
                'name' => $section_name,
                'total' => !empty($order_totals[$section_id]) ? $order_totals[$section_id] : 0,
                'orders' => !empty($order_sections[$section_id]) ? $order_sections[$section_id] : array()
            );

        }

        return $sections;
    }
}
