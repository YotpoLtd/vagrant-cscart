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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

use Tygh\Registry;
use Twigmo\Core\Functions\Order\TwigmoOrder;
use Twigmo\Core\Functions\Image\TwigmoImage;
use Twigmo\Core\Api;
use Twigmo\Api\ApiData;

$format =
!empty($_REQUEST['format']) ? $_REQUEST['format'] : TWG_DEFAULT_DATA_FORMAT;
$api_version =
!empty($_REQUEST['api_version'])
? $_REQUEST['api_version']
: TWG_DEFAULT_API_VERSION;
$response = new ApiData($api_version, $format);

$object = !empty($_REQUEST['object']) ? $_REQUEST['object'] : '';
$lang_code = CART_LANGUAGE;

if (!empty($_REQUEST['language'])) {
    if (in_array(
            $_REQUEST['language'],
            array_keys(Registry::get('languages'))
        )
        ) {
        $lang_code = $_REQUEST['language'];
    }
}

if (!fn_twg_validate_auth()) {
    $response->addError(
        'ERROR_ACCESS_DENIED',
        __('access_denied', $lang_code)
    );
    $response->returnResponse();
}

$data = '';

if (!empty($_REQUEST['data'])) {
    $data = ApiData::parseDocument(
        base64_decode(
            rawurldecode($_REQUEST['data'])
        ),
        $format
    );
    if (!empty($_REQUEST['action'])) {
        $_data = Api::parseApiList($data, $object);
        $data = ApiData::getObjects($_data);
    }
}

$update_actions = array('update', 'delete');

if ($_SERVER['REQUEST_METHOD'] == 'POST'
    &&
    in_array($_REQUEST['action'], $update_actions)) {

    if (empty($data)) {
        $response->addError(
            'ERROR_WRONG_DATA',
            __('twgadmin_wrong_api_data')
        );
    }

    if ($mode == 'post') {
        if ($object == 'users') {
            foreach ($data as $user) {
                if (!empty($user['user_id'])) {
                    if ($_REQUEST['action'] == 'update') {
                        $result = false;
                        $user_data = db_get_row(
                            "SELECT * FROM ?:users WHERE user_id = ?i",
                            $user['user_id']
                        );

                        $notify_user =
                        (!empty($user['notify_updated_user'])
                        &&
                        $user['notify_updated_user'] == 'Y')
                        ? true
                        : false;
                        if (!empty($user['status']) && !$user['is_complete_data']) {
                            $result = db_query(
                                "UPDATE ?:users SET status = ?s WHERE user_id = ?i",
                                $user['status'],
                                $user['user_id']
                            );

                            $force_notification = fn_get_notification_rules(
                                array('notify_user' => $noify_user)
                            );
                            if (!empty($force_notification['C'])
                                && $user['status'] == 'A'
                                && $user_data['status'] == 'D'
                            ) {
                                Mailer::sendMail(
                                    array(
                                        'to' => $user_data['email'],
                                        'from' => 'company_users_department',
                                        'data' => array(
                                            'user_data' => $user_data,
                                        ),
                                        'tpl' => 'profiles/profile_activated.tpl',
                                        'company_id' => $user_data['company_id'],
                                    ),
                                    fn_check_user_type_admin_area($user_data['user_type'])? 'A' : 'C',
                                    $user_data['lang_code']
                                );
                            }
                        }

                        $temp_auth = null;
                        $result = fn_twg_api_update_user(
                            $user,
                            $temp_auth,
                            $notify_user
                        );

                        if (!$result) {
                            $msg = str_replace(
                                '[object_id]',
                                $user['user_id'],
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            );
                            $response->addError(
                                'ERROR_OBJECT_UPDATE',
                                str_replace(
                                    '[object]',
                                    'users',
                                    __(
                                        'twgadmin_wrong_api_object_data'
                                    )
                                )
                            );
                        }

                    } elseif ($_REQUEST['action'] == 'delete') {
                        if (!fn_delete_user($user['user_id'])) {
                            $msg = str_replace(
                                '[object_id]',
                                $user['user_id'],
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            );
                            $response->addError(
                                'ERROR_OBJECT_DELETE',
                                str_replace(
                                    '[object]',
                                    'users',
                                    __(
                                        'twgadmin_wrong_api_object_data'
                                    )
                                )
                            );
                        }
                    }
                } else {
                    $response->addError(
                        'ERROR_WRONG_OBJECT_DATA',
                        str_replace(
                            '[object]',
                            'users',
                            __(
                                'twgadmin_wrong_api_object_data'
                            )
                        )
                    );
                }
            }
        }

        if ($object == 'shipments') {

            if ($_REQUEST['action'] == 'update') {

                $shipments = fn_check_shipment_data($data);

                if ($shipments) {
                    foreach ($shipments as $shipment) {
                        if (empty($shipment['shipment_id'])) {
                            $shipment_id = db_query(
                                "INSERT INTO ?:shipments ?e",
                                $shipment
                            );
                            foreach ($shipment['products'] as $product) {
                                if (!empty($product['amount'])) {
                                    $product['shipment_id'] = $shipment_id;
                                    db_query(
                                        "INSERT INTO ?:shipment_items ?e",
                                        $product
                                    );
                                }
                            }
                        } else {
                            db_query(
                                "UPDATE ?:shipments SET ?u WHERE shipment_id = ?i",
                                $shipment,
                                $shipment['shipment_id']
                            );
                            foreach ($shipment['products'] as $product) {
                                $product['shipment_id'] = $shipment['shipment_id'];
                                if (empty($product['amount'])) {
                                    db_query(
                                        "DELETE FROM ?:shipment_items WHERE item_id = ?i AND shipment_id = ?i",
                                        $product['item_id'],
                                        $product['shipment_id']
                                    );
                                } else {
                                    db_query(
                                        "REPLACE INTO ?:shipment_items ?e",
                                        $product
                                    );
                                }
                            }
                        }
                    }
                } else {
                    $response->addError(
                        'ERROR_WRONG_OBJECT_DATA',
                        str_replace(
                            '[object]',
                            'shipments',
                            __(
                                'twgadmin_wrong_api_object_data'
                            )
                        )
                    );
                }
            } elseif ($_REQUEST['action'] == 'delete') {
                $shipment_ids = array();
                foreach ($data as $shipment) {
                    if (empty($shipment['shipment_id'])) {
                        $response->addError(
                            'ERROR_WRONG_OBJECT_DATA',
                            str_replace(
                                '[object]',
                                'shipments',
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            )
                        );
                    }
                    $shipment_ids[] = $shipment['shipment_id'];
                }

                if (!empty($shipment_ids)) {
                    db_query(
                        'DELETE FROM ?:shipments WHERE shipment_id IN (?a)',
                        $shipment_ids
                    );
                    db_query(
                        'DELETE FROM ?:shipment_items WHERE shipment_id IN (?a)',
                        $shipment_ids
                    );
                }
            }
        }

        if ($object == 'orders') {
            if ($_REQUEST['action'] == 'update') {
                foreach ($data as $order) {
                    // allow to update only order status
                    if (!empty($order['order_id'])) {
                        TwigmoOrder::apiUpdateOrder($order, $response);
                    } else {
                        $response->addError(
                            'ERROR_WRONG_OBJECT_DATA',
                            str_replace(
                                '[object]',
                                'orders',
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            )
                        );
                    }
                }
            }
        }

        if ($object == 'products') {
            if ($_REQUEST['action'] == 'update') {
                foreach ($data as $product) {
                    // allow to update only order status
                    if (!empty($product['product_id'])) {
                        if (!fn_twg_api_update_product(
                                $product,
                                $product['product_id'],
                                $lang_code
                            )) {
                            $msg = str_replace(
                                '[object_id]',
                                $product['product_id'],
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            );
                            $response->addError(
                                'ERROR_OBJECT_UPDATE',
                                str_replace(
                                    '[object]',
                                    'products',
                                    __(
                                        'twgadmin_wrong_api_object_data'
                                    )
                                )
                            );
                        }
                    } else {
                        $response->addError(
                            'ERROR_WRONG_OBJECT_DATA',
                            str_replace(
                                '[object]',
                                'products',
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            )
                        );
                    }
                }
            }
        }

        if ($object == 'categories') {
            if ($_REQUEST['action'] == 'update') {

                foreach ($data as $category) {
                    // allow to update only order status
                    if (!empty($category['category_id'])) {

                        if (!fn_update_category(
                                $category,
                                $category['category_id'],
                                $lang_code
                            )) {
                            $msg = str_replace(
                                '[object_id]',
                                $category['category_id'],
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            );
                            $response->addError(
                                'ERROR_OBJECT_UPDATE',
                                str_replace(
                                    '[object]',
                                    'categories',
                                    __(
                                        'twgadmin_wrong_api_object_data'
                                    )
                                )
                            );
                        } elseif (!empty($category['icon'])) {
                            TwigmoImage::updateIconsByApiData(
                                $category['icon'],
                                $category['category_id'],
                                'category'
                            );
                        }

                    } else {
                        $response->addError(
                            'ERROR_WRONG_OBJECT_DATA',
                            str_replace(
                                '[object]',
                                'categories',
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            )
                        );
                    }
                }
            }
        }

        if ($object == 'images') {
            if ($_REQUEST['action'] == 'delete') {
                foreach ($data as $image) {
                    if (empty($image['image_id'])) {
                        $response->addError(
                            'ERROR_WRONG_OBJECT_DATA',
                            str_replace(
                                '[object]',
                                'images',
                                __(
                                    'twgadmin_wrong_api_object_data'
                                )
                            )
                        );
                        continue;
                    }

                    $image_info = db_get_row(
                        "SELECT pair_id, object_type FROM ?:images_links WHERE image_id = ?i",
                        $image['image_id']
                    );

                    if (!empty($image_info)) {
                        fn_delete_image(
                            $image['image_id'],
                            $image_info['pair_id'],
                            $image_info['object_type']
                        );
                    }
                }
            }
        }

        $response->returnResponse();
    }

}

if ($mode == 'post') {
    if ($_REQUEST['action'] == 'get') {
        $object_name = '';
        $condition = array();
        $options = array(
            'lang_code' => $lang_code
        );
        $sortings = array();
        $result = array();
        $is_paginate = false;
        $items_per_page =
            !empty($_REQUEST['items_per_page'])
            ? $_REQUEST['items_per_page']
            : TWG_RESPONSE_ITEMS_LIMIT;

        if ($object == 'statuses') {
            $order_statuses = fn_get_statuses(
                STATUSES_ORDER,
                true,
                false,
                false,
                $lang_code
            );

            $api_statuses = array();
            foreach ($order_statuses as $k => $v) {
                $status = array (
                    'code' => $k,
                    'description' => $v,
                    'color' => '#28ABF6'
                );

                if ($k == 'C' || $k == 'P') {
                    $status['color'] = '#97CF4D';
                } elseif ($k == 'D' || $k == 'F') {
                    $status['color'] = '#FF5215';
                } elseif ($k == 'I') {
                    $status['color'] = '#D2D2D2';
                } elseif ($k == 'O') {
                    $status['color'] = '#FF9522';
                }

                $api_statuses[] = $status;
            }

            $result['orders']['status'] = $api_statuses;

            // hardcoded statuses
            $result['products']['status'] = fn_twg_api_get_base_statuses(
                true,
                $lang_code
            );
            $result['categories']['status'] = fn_twg_api_get_base_statuses(
                true,
                $lang_code
            );
            $result['users']['status'] = fn_twg_api_get_base_statuses(false);
            $response->setData($result);

        } elseif ($object == 'users') {
            $auth = null;
            $_REQUEST['user_type'] = 'C';
            list($users, $search) = fn_get_users($_REQUEST, $auth, $items_per_page);

            $u_ids = array();
            foreach ($users as $k => $v) {
                $u_ids[] = $v['user_id'];
            }

            if (empty($users)) {
                $response->returnResponse();
            }

            $response->setResponseList(Api::getAsList($object, $users));
            $is_paginate = true;

        } elseif ($object == 'orders' || $object == 'order_sections') {

            if (!fn_api_get_orders_search_params($lang_code)) {
                $response->returnResponse();
            }

            if (!empty($_REQUEST['status'])) {
                $_REQUEST['status'] = unserialize($_REQUEST['status']);
            }

            list($orders, $search, $totals) = fn_get_orders(
                $_REQUEST,
                $items_per_page, true
            );

            $response->setMeta(
                !empty($totals['gross_total']) ? $totals['gross_total'] : 0,
                'gross_total'
            );
            $response->setMeta(
                !empty($totals['totally_paid']) ? $totals['totally_paid'] : 0,
                'totally_paid'
            );

            if (empty($orders)) {
                $response->returnResponse();
            }

            if ($object == 'order_sections') {
                $sections = TwigmoOrder::getOrderSections($orders, $_REQUEST);
                $response->setResponseList(
                    Api::getAsList(
                        $object,
                        $sections
                    )
                );
            } else {
                $response->setResponseList(
                    TwigmoOrder::getOrdersAsApiList(
                        $orders,
                        $lang_code
                    )
                );
            }
            $is_paginate = true;

        } elseif ($object == 'products') {
            fn_twg_set_response_products(
                $response,
                $_REQUEST,
                $items_per_page,
                $lang_code
            );

        } elseif ($object == 'categories' || $object == 'categories_paginated') {

            if ($object == 'categories') {
                fn_twg_set_response_categories($response, $_REQUEST, 0, $lang_code);
            } elseif ($object == 'categories_paginated') {
                fn_twg_set_response_categories(
                    $response,
                    $_REQUEST,
                    $items_per_page,
                    $lang_code
                );
            }

        } elseif ($object == 'shipments') {

            $_REQUEST['advanced_info'] = true;
            list($shipments, $search, $totals) = fn_get_shipments_info(
                $_REQUEST,
                $items_per_page
            );

            if (!empty($_REQUEST['order_id'])) {
                $items_amount = db_get_row(
                    "SELECT SUM(?:order_details.amount) as amount,
                    SUM(?:shipment_items.amount) as shipped_amount
                    FROM ?:order_details
                    LEFT JOIN ?:shipment_items
                    ON ?:shipment_items.item_id = ?:order_details.item_id
                    WHERE ?:order_details.order_id = ?i
                    GROUP BY ?:order_details.order_id",
                    $_REQUEST['order_id']
                );
                $not_shipped = $items_amount['amount'] - $items_amount['shipped_amount'];
                $need_shipment = ($not_shipped > 0) ? 'Y' : 'N';
                $response->setMeta(
                    $need_shipment,
                    'need_shipment'
                );

            }

            $response->setResponseList(Api::getAsList($object, $shipments));
            $is_paginate = true;

        } elseif ($object == 'order_stats') {

            $today = getdate(TIME);
            $orders_stats = array();

            $wday = empty($today['wday'])   ? "6" :
                    (($today['wday'] == 1)  ? "0" :
                                                    $today['wday'] - 1);
            $wstart = getdate(strtotime("-$wday day"));

            $stats_periods = array (
                'day' => mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']),
                'week' => mktime(0, 0, 0, $wstart['mon'], $wstart['mday'], $wstart['year']),
                'month' => mktime(0, 0, 0, $today['mon'], 1, $today['year']),
                'year' => mktime(0, 0, 0, 1, 1, $today['year'])
            );

            $days_in_prev_month = (int) date(
                't',
                mktime(
                    0,
                    0,
                    0,
                    ($today['mon'] -1),
                    1,
                    $today['year']
                )
            );
            $lessThanPrevMonth = $today['mday'] < $days_in_prev_month;
            $prev_mday = $lessThanPrevMonth ? $today['mday'] : $days_in_prev_month;
            $prev_periods =  array (
                'day' => array (
                    'start' => mktime(
                        0,
                        0,
                        0,
                        $today['mon'],
                        $today['mday'] - 1,
                        $today['year']
                    ),
                    'end' => mktime(
                        $today['hours'],
                        $today['minutes'],
                        $today['seconds'],
                        $today['mon'],
                        $today['mday'] - 1,
                        $today['year']
                    )
                ),
                'week' => array (
                    'start' => mktime(
                        0,
                        0,
                        0,
                        $wstart['mon'],
                        $wstart['mday'] - 7,
                        $wstart['year']
                    ),
                    'end' => mktime(
                        $today['hours'],
                        $today['minutes'],
                        $today['seconds'],
                        $today['mon'],
                        $today['mday'] - 7,
                        $today['year']
                    )
                ),
                'month' => array (
                    'start' => mktime(
                        0,
                        0,
                        0,
                        ($today['mon'] -1),
                        1,
                        $today['year']
                    ),
                    'end' => mktime(
                        0,
                        0,
                        0,
                        ($today['mon'] -1),
                        $prev_mday,
                        $today['year']
                    ),
                ),
                'year' => array (
                    'start' => mktime(
                        0,
                        0,
                        0,
                        1,
                        1,
                        ($today['year'] - 1)
                    ),
                    'end' => mktime(
                        0,
                        0,
                        0,
                        $today['mon'],
                        $today['mday'],
                        ($today['year'] -1)
                    )
                )
            );

            $period_stats = array();
            $paid_statuses = db_get_fields(
                "SELECT status
                FROM ?:status_data
                WHERE type = ?s
                AND param = ?s
                AND value = ?s",
                'O',
                'inventory',
                'D');
            foreach ($stats_periods as $title => $time_from) {
                $status_orders = db_get_array(
                    "SELECT status,
                        COUNT(order_id) as amount,
                        SUM(total) as total,
                        MAX(timestamp) as last_timestamp
                    FROM ?:orders
                    WHERE timestamp >= ?i
                    AND timestamp <= ?i
                    GROUP BY status",
                    $time_from,
                    TIME
                );

                $totals = db_get_row(
                    "SELECT SUM(IF(status in (?a), total, 0)) as total_paid,
                        SUM(total) as total,
                        COUNT(order_id) as order_amount
                     FROM ?:orders
                     WHERE timestamp >= ?i AND timestamp <= ?i",
                    $paid_statuses,
                    $time_from,
                    TIME
                );

                $item_amount = db_get_field(
                    "SELECT COUNT(?:order_details.item_id) as product_amount
                     FROM ?:orders, ?:order_details
                     WHERE ?:orders.timestamp >= ?i
                     AND ?:orders.timestamp < ?i
                     AND ?:orders.order_id = ?:order_details.order_id",
                    $time_from,
                    TIME
                );

                $prev_item_amount = db_get_field(
                    "SELECT COUNT(?:order_details.item_id) as product_amount
                     FROM ?:orders, ?:order_details
                     WHERE ?:orders.timestamp >= ?i
                     AND ?:orders.timestamp < ?i
                     AND ?:orders.order_id = ?:order_details.order_id",
                    $prev_periods[$title]['start'],
                    $prev_periods[$title]['end']
                );

                $prev_totals  = db_get_row(
                    "SELECT SUM(IF(status in (?a), total, 0)) as total_paid,
                     SUM(total) as total,
                     COUNT(order_id) as order_amount
                     FROM ?:orders
                     WHERE timestamp >= ?i AND timestamp < ?i",
                    $paid_statuses,
                    $prev_periods[$title]['start'],
                    $prev_periods[$title]['end']
                );

                $period_total = empty($totals['total_paid']) ? 0 : $totals['total_paid'];
                $order_amount = empty($totals['order_amount']) ? 0 : $totals['order_amount'];
                $period_total_paid = empty($totals['total_paid']) ? 0 : $totals['total_paid'];
                $total_change = empty($prev_totals['total_paid']) ? 0 : $prev_totals['total_paid'];
                $order_amount_change = empty($prev_totals['order_amount']) ? 0 : $prev_totals['order_amount'];
                $total_paid_change = empty($prev_totals['total_paid']) ? 0 : $prev_totals['total_paid'];
                $item_amount_change = $prev_item_amount;

                $stat = array (
                    'title' => $title,
                    'time_from' => $time_from,
                    'total_paid' => $period_total_paid,
                    'total_paid_change' => $total_paid_change,
                    'total' => $period_total,
                    'total_change' => $total_change,
                    'order_amount' => $order_amount,
                    'order_amount_change' => $order_amount_change,
                    'item_amount' => $item_amount
                    // There is a typo in admin app
                    // (item_amount_change interpreted as item_amount) so comment this line
                    // 'item_amount_change' => $item_amount_change
                );

                if (!empty($status_orders)) {
                    $stat['status_stats'] = array('status_stat' => $status_orders);
                }
                $period_stats[] = $stat;
            }

            $response->setData(array('period' => $period_stats), 'periods');

            // Get latest orders
            $search_params = array (
                'sort_by' => 'date',
                'sort_order' => 'desc'
            );
            list($orders) = fn_get_orders($search_params, $items_per_page, true);
            $orders = TwigmoOrder::getOrdersAsApiList($orders, $lang_code);
            $response->setData($orders, 'latest_orders');

            $response->returnResponse('orders_statistics');
        } else {
            if (!empty($search) && !empty($search['sort_by'])) {
                $sortings = array (
                    'sort_by' => $search['sort_by'],
                    'sort_order' =>
                    (!empty($search['sort_order']) && $search['sort_order'] == 'desc') ? 'desc' : 'asc'
                );
            }

            $response->setResponseList(
                Api::getApiSchemaData(
                    $object,
                    $condition,
                    array(),
                    $options,
                    $sortings
                )
            );
        }
        $pagination_params = array(
            'items_per_page' => !empty($items_per_page)? $items_per_page : TWG_RESPONSE_ITEMS_LIMIT,
            'page' => !empty($_REQUEST['page'])? $_REQUEST['page'] : 1
        );
        if ($is_paginate) {
            fn_twg_set_response_pagination($response, $pagination_params);
        }

        $response->returnResponse($object);
    }

    if ($_REQUEST['action'] == 'details') {

         if ($object == 'shipment_data') {
            $shippings = db_get_array(
                "SELECT a.shipping_id,
                 b.shipping
                 FROM ?:shippings as a
                 LEFT JOIN ?:shipping_descriptions as b
                 ON a.shipping_id = b.shipping_id AND b.lang_code = ?s
                 WHERE a.status = ?s
                 ORDER BY a.position",
                DESCR_SL,
                'A'
            );

            $carriers_data = fn_twg_get_carriers();

            $carriers = array();
            foreach ($carriers_data as $k => $v) {
                $carriers[] = array (
                    'carrier_id' => $k,
                    'carrier' => $v
                );
            }

            $result = array (
                'shippings' => Api::getAsList('shippings', $shippings),
                'carriers' => Api::getAsList('carriers', $carriers)
            );

            $response->setData($result);
            $response->returnResponse($object);
        }

        if (empty($_REQUEST['id'])) {
            $response->addError(
                'ERROR_WRONG_OBJECT_DATA',
                str_replace(
                    '[object]',
                    $object,
                    __(
                        'twgadmin_wrong_api_object_data'
                    )
                )
            );
            $response->returnResponse();
        }

        if ($object == 'orders') {
            $order_id = $_REQUEST['id'];
            $order = TwigmoOrder::apiGetOrderDetails($order_id);
            if (empty($order)) {
                $response->addError(
                    'ERROR_OBJECT_WAS_NOT_FOUND',
                    str_replace(
                        '[object]',
                        $object,
                        __(
                            'twgadmin_object_was_not_found'
                        )
                        ));
                $response->returnResponse();
            }

            $response->setData($order);
            $response->returnResponse('order');

        } elseif ($object == 'products') {

            $product = fn_twg_get_api_product_data($_REQUEST['id'], $lang_code);

            if (empty($product)) {
                $response->addError(
                    'ERROR_OBJECT_WAS_NOT_FOUND',
                    str_replace(
                        '[object]',
                        $object,
                        __(
                            'twgadmin_object_was_not_found'
                        )
                    )
                );
                $response->returnResponse();
            }

            $response->setData($product);
            $response->returnResponse('product');

        } elseif ($object == 'categories') {

            $category = fn_twg_get_api_category_data($_REQUEST['id'], $lang_code);

            if (empty($category)) {
                $response->addError(
                    'ERROR_OBJECT_WAS_NOT_FOUND',
                    str_replace(
                        '[object]',
                        $object,
                        __(
                            'twgadmin_object_was_not_found'
                        )
                    )
                );
                $response->returnResponse();
            }

            $response->setData($category);
            $response->returnResponse('category');

        } else {
            // get object data by scheme where id is a primary
            // key in database and scheme
            fn_twg_api_get_object($response, $object, $_REQUEST);
        }
    }
    if ($_REQUEST['action'] == 'edit_css') {
        $_SESSION['current_path'] = "/basic/templates/addons/twigmo/";
        fn_redirect(Registry::get('config.admin_index') . "?dispatch=template_editor.manage", true);
    }
}

function fn_api_get_orders_search_params($lang_code = CART_LANGUAGE)
{
    if (!empty($_REQUEST['shipping_name'])) {
        $shipping_ids = db_get_fields(
            "SELECT shipping_id
             FROM ?:shipping_descriptions
             WHERE shipping LIKE ?l AND lang_code = ?s",
            "%$_REQUEST[shipping_name]%",
            $lang_code
        );

        if (empty($shipping_ids)) {
            return false;
        }

        $_REQUEST['shippings'] = $shipping_ids;
    }

    $condition = '';
    $tables = array();

    if (!empty($_REQUEST['sname'])) {
        // search in products
        $tables[] = '?:product_descriptions';
        $tables[] = '?:order_details';
        $sub_condition = array (
            db_quote(
                "?:orders.order_id = ?:order_details.order_id
                 AND ?:order_details.product_id = ?:product_descriptions.product_id
                 AND ?:product_descriptions.product LIKE ?l
                 AND ?:product_descriptions.lang_code = ?s",
                "%$_REQUEST[sname]%",
                $lang_code
            )
        );

        // search in customer names
        $arr = explode(' ', $_REQUEST['sname']);
        if (sizeof($arr) == 2) {
            $sub_condition[] = db_quote(
                "?:orders.firstname LIKE ?l AND ?:orders.lastname LIKE ?l",
                "%$arr[0]%",
                "%$arr[1]%"
            );
        } else {
            $sub_condition[] = db_quote(
                "?:orders.firstname LIKE ?l OR ?:orders.lastname LIKE ?l",
                "%$_REQUEST[sname]%",
                "%$_REQUEST[sname]%"
            );
        }

        // search in emails
        $sub_condition[] = db_quote(
            "?:orders.email LIKE ?l",
            "%$_REQUEST[sname]%"
        );

        // search in order id
        $sub_condition[] = db_quote(
            "?:orders.order_id = ?i",
            $_REQUEST['sname']
        );

        $condition .= " AND ((" . implode($sub_condition, ") OR (") . "))";
    }

    if (!empty($_REQUEST['product_name'])) {
        $tables[] = '?:product_descriptions';
        $tables[] = '?:order_details';
        $condition .= db_quote(
            " AND ?:orders.order_id = ?:order_details.order_id
             AND ?:order_details.product_id = ?:product_descriptions.product_id
             AND ?:product_descriptions.product LIKE ?l
             AND ?:product_descriptions.lang_code = ?s",
            "%$_REQUEST[product_name]%",
            $lang_code
        );
    }

    if (!empty($_REQUEST['payment'])) {
        $tables[] = '?:payment_descriptions';
        $condition .= db_quote(
            " AND ?:payment_descriptions.payment_id = ?:orders.payment_id
             AND ?:payment_descriptions.payment LIKE ?l
             AND ?:payment_descriptions.lang_code = ?s",
            "%$_REQUEST[payment]%",
            $lang_code
        );
    }

    if (!empty($condition)) {
        $tables[] = '?:orders';
        $tables = array_unique($tables);
        $order_ids = db_get_fields(
            "SELECT DISTINCT ?:orders.order_id
             FROM " . implode(', ', $tables) . "
             WHERE 1 " . $condition
        );

        if (empty($order_ids)) {
            return false;
        }

        if (!empty($_REQUEST['order_id'])) {
            $_REQUEST['order_id'] = array_intersect(
                $_REQUEST['order_id'],
                $order_ids
            );
        } else {
            $_REQUEST['order_id'] = $order_ids;
        }
    }

    return true;
}

function fn_check_shipment_data($data)
{
    $shipments = array();

    foreach ($data as $k => $v) {

        if (!empty($v['shipment_id'])) {

            $shipment_info =  db_get_row(
                "SELECT ?:shipments.*, ?:shipment_items.order_id
                 FROM ?:shipments
                 LEFT JOIN ?:shipment_items
                 ON ?:shipments.shipment_id = ?:shipment_items.shipment_id
                 WHERE ?:shipments.shipment_id = ?i",
                $v['shipment_id']
            );

            if (!$shipment_info) {
                return false;
            }
            $shipment_items =  db_get_hash_single_array(
                "SELECT item_id, amount
                 FROM ?:shipment_items
                 WHERE shipment_id = ?i",
                array('item_id', 'amount'),
                $v['shipment_id']
            );

            $v = array_merge($shipment_info, $v);

        } elseif (empty($v['is_complete_data'])) {
             return false;
        }

        $order_info = fn_get_order_info($v['order_id'], false, true, true);

        if (!empty($v['shipment_id']) && !empty($shipment_items)) {
            foreach ($shipment_items as $item_id => $amount) {
                if (!isset($order_info['items'][$item_id])) {
                    return false;
                }
                $order_info['items'][$item_id]['shipped_amount'] -= $amount;
            }
        }

        if (empty($order_info)) {
            return false;
        }

        if (empty($v['shipping_id'])) {
            $v['shipping_id'] = $order_info['shipping_ids'];
        }

        if (empty($v['timestamp'])) {
            $v['timestamp'] = TIME;
        }

        $items = array();

        foreach ($v['products'] as $product) {
            if (!$product['is_complete_data']) {
                return false;
            }

            $item_id = $product['item_id'];

            if (!isset($order_info['items'][$item_id])) {
                return false;
            }

            $amount = intval($product['amount']);

            if ($amount > ($order_info['items'][$item_id]['amount']
                             - $order_info['items'][$item_id]['shipped_amount'])
                ) {
                return false;
            }

            $items[] = array (
                'item_id' => $item_id,
                'order_id' => $v['order_id'],
                'product_id' => $order_info['items'][$item_id]['product_id'],
                'amount' => $amount
            );
        }

        unset($v['products']);
        $v['products'] = $items;
        $shipments[] = $v;
    }

    return $shipments;
}
