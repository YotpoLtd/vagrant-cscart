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

use Twigmo\Api\ApiData;
use Twigmo\Core\Api;
use Twigmo\Core\Functions\Order\TwigmoOrder;
use Tygh\Session;
use Tygh\Registry;
use Tygh\Mailer;

$format = !empty($_REQUEST['format'])?
    $_REQUEST['format'] :
    TWG_DEFAULT_DATA_FORMAT;

$api_version = !empty($_REQUEST['api_version'])?
    $_REQUEST['api_version'] :
    TWG_DEFAULT_API_VERSION;

$response = new ApiData($api_version, $format);

$lang_code = CART_LANGUAGE;
$items_per_page = !empty($_REQUEST['items_per_page'])?
    $_REQUEST['items_per_page'] :
    TWG_RESPONSE_ITEMS_LIMIT;

if (!empty($_REQUEST['language'])) {
    if (in_array($_REQUEST['language'], array_keys(Registry::get('languages')))) {
        $lang_code = $_REQUEST['language'];
    }
}

$mode = Registry::get('runtime.mode');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mode == 'post') {

    $meta = fn_twg_init_api_meta($response);

    if ($meta['action'] == 'login') {

        $login = !empty($_REQUEST['login']) ? $_REQUEST['login'] : '';
        $password = !empty($_REQUEST['password']) ? $_REQUEST['password'] : '';

        // Support login by email even if it is disabled
        // replace email in login name with the login corresponding to entered email
        // REMOVE AFTER adding login settings to the application
        if ((Registry::get('settings.General.use_email_as_login') != 'Y')
            && fn_validate_email($login)) {
            $login = db_get_field(
                'SELECT user_login FROM ?:users WHERE email = ?s',
                $login
            );
        }

        if (!$user_data = fn_twg_api_customer_login($login, $password)) {
            $response->addError(
                'ERROR_CUSTOMER_LOGIN_FAIL',
                __('error_incorrect_login')
            );
        }

        $user_info_params = array(
            'mode' => $mode,
            'user_id' => $user_data['user_id']
        );
        $profile = fn_twg_get_user_info($user_info_params);
        if (fn_allowed_for('MULTIVENDOR')) {
            $profile['company_data'] = !empty($_SESSION['auth']['company_id'])? fn_get_company_data($_SESSION['auth']['company_id']): array();
        } else {
            $profile['company_data'] = array();
        }
        $_profile = array_merge(
            $profile,
            array('cart' => fn_twg_api_get_session_cart($_SESSION['cart'], $lang_code))
        );

        $response->setData($_profile);

    } elseif ($meta['action'] == 'add_to_cart') {
        // add to cart
        $data = fn_twg_get_api_data($response, $format);

        $ids = fn_twg_api_add_product_to_cart(array($data), $_SESSION['cart']);

        $result = fn_twg_api_get_session_cart($_SESSION['cart'], $lang_code);
        $response->setData($result);
        $response->setMeta(!empty($ids) ? array_pop(array_keys($ids)) : 0, 'added_id');

    } elseif ($meta['action'] == 'delete_from_cart') {
        // delete from cart
        $data = fn_twg_get_api_data($response, $format);
        $cart = & $_SESSION['cart'];
        $auth = & $_SESSION['auth'];

        foreach ($data as $cart_id) {
            fn_delete_cart_product($cart, $cart_id . '');
        }
        if (fn_cart_is_empty($cart)) {
            fn_clear_cart($cart);
        }

        fn_save_cart_content($cart, $auth['user_id']);

        $cart['recalculate'] = true;
        fn_calculate_cart_content($cart, $auth, 'S', true, 'F', true);

        $result = fn_twg_api_get_session_cart($cart, $lang_code);
        $response->setData($result);

    } elseif ($meta['action'] == 'update_cart_amount') {
        $cart = & $_SESSION['cart'];
        $auth = & $_SESSION['auth'];
        $cart_id = $_REQUEST['cart_id'] . '';
        if (empty($cart['products'][$cart_id])) {
            return;
        }
        $products = $cart['products'];
        foreach ($products as $_key => $_data) {
            if (empty($_data['amount'])
                && !isset($cart['products'][$_key]['extra']['parent'])) {
                fn_delete_cart_product($cart, $_key);
            }
        }
        $products[$cart_id]['amount'] = $_REQUEST['amount'];
        fn_add_product_to_cart($products, $cart, $auth, true);
        fn_save_cart_content($cart, $auth['user_id']);
        $cart['recalculate'] = true;
        fn_calculate_cart_content($cart, $auth, 'S', true, 'F', true);

        $result = fn_twg_api_get_session_cart($cart, $lang_code);
        $response->setData($result);

    } elseif ($meta['action'] == 'logout') {
        fn_twg_api_customer_logout();

    } elseif ($meta['action'] == 'send_form') {
        fn_send_form(
            $_REQUEST['page_id'],
            empty($_REQUEST['form_values']) ? array() : $_REQUEST['form_values']
        );

    } elseif ($meta['action'] == 'apply_coupon') {
        $gift_certificates_are_active = Registry::get('addons.gift_certificates.status') == 'A';
        $mode = $meta['action'];
        $cart = & $_SESSION['cart'];
        $cart['pending_coupon'] = $_REQUEST['coupon_code'];
        $cart['recalculate'] = true;
        if ($gift_certificates_are_active) {
            include_once(Registry::get('config.dir.addons') . 'gift_certificates/controllers/frontend/checkout.post.php');
        }
        fn_calculate_cart_content($cart, $_SESSION['auth'], 'E', true, 'F', true);
        $response->setData(fn_twg_api_get_session_cart($cart));
    } elseif ($meta['action'] == 'delete_coupon') {
        $cart = & $_SESSION['cart'];
        unset($cart['coupons'][$_REQUEST['coupon_code']], $cart['pending_coupon']);
        $cart['recalculate'] = true;
        fn_calculate_cart_content($cart, $_SESSION['auth'], 'E', true, 'F', true);
        $response->setData(fn_twg_api_get_session_cart($cart));

    } elseif ($meta['action'] == 'delete_use_certificate') {
        $cart = & $_SESSION['cart'];
        $gift_cert_code =
            empty($_REQUEST['gift_cert_code'])
            ? ''
            : strtoupper(trim($_REQUEST['gift_cert_code']));
        fn_delete_gift_certificate_in_use($gift_cert_code, $cart);
        $cart['recalculate'] = true;
        fn_calculate_cart_content($cart, $_SESSION['auth'], 'E', true, 'F', true);
        $response->setData(fn_twg_api_get_session_cart($cart));

    } elseif ($meta['action'] == 'place_order') {

        $data = fn_twg_get_api_data($response, $format);
        $order_id = TwigmoOrder::apiPlaceOrder($data, $response, $lang_code);

        if (empty($order_id)) {
            if (!fn_twg_set_internal_errors($response, 'ERROR_FAIL_POST_ORDER')) {
                $response->addError(
                    'ERROR_FAIL_POST_ORDER',
                    __(
                        'fail_post_order',
                        $lang_code
                    )
                );
            }
            $response->returnResponse();
        }
        TwigmoOrder::returnPlacedOrders(
            $order_id,
            $response,
            $items_per_page,
            $lang_code
        );

    } elseif ($meta['action'] == 'update') {

        if ($meta['object'] == 'cart') {
            // update cart
            $data = fn_twg_get_api_data($response, $format);

            $cart = & $_SESSION['cart'];
            fn_clear_cart($cart);

            if (!empty($data['products'])) {
                fn_twg_api_add_product_to_cart($data['products'], $cart);
            }

            $result = fn_twg_api_get_session_cart($cart, $lang_code);
            $response->setData($result);

        } elseif ($meta['object'] == 'users') {

            $user = fn_twg_get_api_data($response, $format);
            fn_twg_api_process_user_data($user, $response, $lang_code);

        } elseif ($meta['object'] == 'profile') {
            // For 2.0, users object - for iphone app
            $user_data = fn_twg_get_api_data($response, $format);

            if ($_SESSION['auth']['user_id'] != $user_data['user_id']) {
                $response->addError(
                    'ERROR_ACCESS_DENIED',
                    __('access_denied', $lang_code)
                );
                $response->returnResponse();
            }

            if (!isset($user_data['password1'])) {
                $user_data['password1'] = '';
            }
            $result = fn_update_user(
                $user_data['user_id'],
                $user_data,
                $_SESSION['auth'],
                !$user_data['copy_address'],
                true
            );
            if (!$result) {
                if (!fn_twg_set_internal_errors(
                        $response,
                        'ERROR_FAIL_CREATE_USER'
                    )) {
                    $response->addError(
                        'ERROR_FAIL_CREATE_USER',
                        __('twgadmin_fail_create_user')
                    );
                }
                $response->returnResponse();
            }
            $_SESSION['cart']['user_data'] = fn_get_user_info(
                $_SESSION['auth']['user_id']
            );
    //        fn_api_set_cart_user_data($data['user'], $response, $lang_code);

            $user_info_params = array(
                'mode' => $mode,
                'user_id' => $_SESSION['auth']['user_id']
            );
            $profile = fn_twg_get_user_info($user_info_params);
            $_profile = array_merge(
                $profile,
                array('cart' => fn_twg_api_get_session_cart($_SESSION['cart'], $lang_code))
            );
            $response->setData($_profile);

        } elseif ($meta['object'] == 'cart_profile') {
            // For anonymous chekcout
            $user_data = fn_twg_get_api_data($response, $format);
            if ($user_data['copy_address']) {
                fn_fill_address($user_data, fn_get_profile_fields(), false);
            }
            $_SESSION['cart']['user_data'] = $user_data;
            //fn_api_set_cart_user_data($user_data, $response, $lang_code);

        } elseif ($meta['object'] == 'payment_methods') {
            $cart = & $_SESSION['cart'];
            $auth = & $_SESSION['auth'];
            if (!empty($_REQUEST['payment_info']) and isset($_REQUEST['payment_info']['payment_id'])) {
                $cart['payment_id'] = (int) $_REQUEST['payment_info']['payment_id'];
                $cart['payment_updated'] = true;
                $cart['extra_payment_info'] = $_REQUEST['payment_info'];
                if (!empty($cart['extra_payment_info']['card_number'])) {
                    $cart['extra_payment_info']['secure_card_number'] = preg_replace('/^(.+?)([0-9]{4})$/i', '***-$2', $cart['extra_payment_info']['card_number']);
                }
                fn_update_payment_surcharge($cart, $auth);
            }

            fn_save_cart_content($cart, $auth['user_id']);

            // Recalculate the cart
            $cart['recalculate'] = true;
            fn_calculate_cart_content($cart, $auth, 'E', true, 'F', true);
            $result = fn_twg_api_get_session_cart($cart, $lang_code);
            $response->setData($result);
        } else {
            $response->addError(
                'ERROR_UNKNOWN_REQUEST',
                __(
                    'unknown_request',
                    $lang_code
                )
            );
            $response->returnResponse();
        }

    } elseif ($meta['action'] == 'get') {

        if ($meta['object'] == 'page') {
            $response->setData(fn_twg_api_get_page($_REQUEST['page_id']));

        } elseif ($meta['object'] == 'cart') {
            $result = fn_twg_api_get_session_cart($_SESSION['cart'], $lang_code);
            $response->setData($result);

        } elseif ($meta['object'] == 'products') {
            fn_twg_set_response_products(
                $response,
                $_REQUEST,
                $items_per_page,
                $lang_code
            );
            if (fn_allowed_for('MULTIVENDOR')) {
                fn_twg_add_response_vendors($response, $_REQUEST);
            }
        } elseif ($meta['object'] == 'categories') {
            fn_twg_set_response_categories(
                $response,
                $_REQUEST,
                $items_per_page,
                $lang_code
            );

        } elseif ($meta['object'] == 'catalog') {
            if (Registry::get('settings.General.show_products_from_subcategories') == 'Y') {
                $_REQUEST['subcats'] = 'Y';
            }
            fn_twg_set_response_catalog(
                $response,
                $_REQUEST,
                $items_per_page,
                $lang_code
            );

        } elseif ($meta['object'] == 'orders') {

            $_auth = & $_SESSION['auth'];
            $params = $_REQUEST;

            if (!empty($_auth['user_id'])) {
                $params['user_id'] = $_auth['user_id'];
            } elseif (!empty($_auth['order_ids'])) {
                $params['order_id'] = $_auth['order_ids'];
            } else {
                $response->addError(
                    'ERROR_ACCESS_DENIED',
                    __('access_denied')
                );
                $response->returnResponse();
            }
            $params['page'] = !empty($params['page'])? $params['page']: 1;
            list($orders, $params, $totals) = fn_get_orders(
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

            $response->setResponseList(
                TwigmoOrder::getOrdersAsApiList($orders, $lang_code)
            );
            $pagination_params = array(
                'total_items' => $params['total_items'],
                'items_per_page' => !empty($items_per_page)? $items_per_page : TWG_RESPONSE_ITEMS_LIMIT,
                'page' => !empty($params['page'])? $params['page'] : 1
            );
            fn_twg_set_response_pagination($response, $pagination_params);

        } elseif ($meta['object'] == 'placed_order') {
            TwigmoOrder::checkIfOrderAllowed(
                $_REQUEST['order_id'],
                $_SESSION['auth'],
                $response
            );
            TwigmoOrder::returnPlacedOrders(
                $_REQUEST['order_id'],
                $response,
                $items_per_page,
                $lang_code
            );
        } elseif ($meta['object'] == 'homepage') {

            fn_twg_set_response_homepage($response);

        } elseif ($meta['object'] == 'payment_methods') {
            $cart = &$_SESSION['cart'];
            $auth = &$_SESSION['auth'];

            // Update shipping info
            if (!empty($_REQUEST['shipping_ids'])) {
                $params = array(
                    'cart' => & $cart,
                    'auth' => & $auth
                );
                $product_groups = fn_twg_api_get_product_groups($params);
                fn_checkout_update_shipping($cart, $_REQUEST['shipping_ids']);
            }

            $payment_methods = fn_twg_get_payment_methods();
            if (!empty($payment_methods['payment'])) {
                foreach ($payment_methods['payment'] as $k => $v) {
                    if ($options = fn_twg_get_payment_options($v['payment_id'])) {
                        $payment_methods['payment'][$k]['options'] = $options;
                    }
                }
                $cart['recalculate'] = true;
                $cart['calculate_shipping'] = true;
                fn_calculate_cart_content($cart, $auth, 'A');
                $response->setData(
                    array(
                        'payments' => $payment_methods['payment'],
                        'cart' => fn_twg_api_get_session_cart($cart, $lang_code)
                    )
                );
            }

        } elseif ($meta['object'] == 'shipping_methods') {

            $_SESSION['cart']['calculate_shipping'] = true;
            $params = array(
              'cart' => & $_SESSION['cart'],
              'auth' => & $_SESSION['auth']
            );
            $product_groups = fn_twg_api_get_product_groups($params);

            $shipping_methods = Api::getAsList(
                'companies_rates',
                $product_groups
            );

            $shipping_methods['shipping_failed'] =
             !empty($_SESSION['cart']['shipping_failed'])
             ? $_SESSION['cart']['shipping_failed']
             : false;

            $response->setData($shipping_methods);

        } elseif ($meta['object'] == 'product_files') {
            $file_url =
            array(
                'fileUrl' => fn_url(
                    "orders.get_file&ekey="
                    . $_REQUEST['ekey']
                    . "&file_id="
                    . $_REQUEST['file_id']
                    . "&product_id="
                    . $_REQUEST['product_id'],
                    AREA,
                    'rel',
                    '&'
                )
            );
            $response->setData($file_url);
        } elseif ($meta['object'] == 'errors') {
            $response->returnResponse();
        } else {
            $response->addError('ERROR_UNKNOWN_REQUEST', __('unknown_request'));
            $response->returnResponse();
        }

    } elseif ($meta['action'] == 'details') {

        if ($meta['object'] == 'products') {
            $object = fn_twg_get_api_product_data($_REQUEST['id'], $lang_code);
            $title = 'product';

            // Set recently viewed products history
            if (!empty($_SESSION['recently_viewed_products'])) {
                $recently_viewed_product_id = array_search(
                    $_REQUEST['id'],
                    $_SESSION['recently_viewed_products']
                );
                // Existing product will be moved on the top of the list
                if ($recently_viewed_product_id !== FALSE) {
                    // Remove the existing product to put it on the top later
                    unset($_SESSION['recently_viewed_products'][$recently_viewed_product_id]);
                    // Re-sort the array
                    $_SESSION['recently_viewed_products'] = array_values(
                        $_SESSION['recently_viewed_products']
                    );
                }
                array_unshift($_SESSION['recently_viewed_products'], $_REQUEST['id']);
            } elseif (empty($_SESSION['recently_viewed_products'])) {
                $_SESSION['recently_viewed_products'] = array($_REQUEST['id']);
            }

            if (count($_SESSION['recently_viewed_products']) > MAX_RECENTLY_VIEWED) {
                array_pop($_SESSION['recently_viewed_products']);
            }

            // Increase product popularity
            if (empty($_SESSION['products_popularity']['viewed'][$_REQUEST['id']])) {
                $_data = array (
                    'product_id' => $_REQUEST['id'],
                    'viewed' => 1,
                    'total' => POPULARITY_VIEW
                );

                db_query(
                    "INSERT INTO ?:product_popularity ?e ON DUPLICATE KEY UPDATE viewed = viewed + 1, total = total + ?i",
                    $_data,
                    POPULARITY_VIEW
                );

                $_SESSION['products_popularity']['viewed'][$_REQUEST['id']] = true;
            }

        } elseif ($meta['object'] == 'categories') {
            $object = fn_twg_get_api_category_data($_REQUEST['id'], $lang_code);
            $title = 'category';

        } elseif ($meta['object'] == 'orders') {
            $order_id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            TwigmoOrder::checkIfOrderAllowed($order_id, $_SESSION['auth'], $response);
            $object = TwigmoOrder::apiGetOrderDetails($order_id);
            $title = 'order';

        } elseif ($meta['object'] == 'order') {

            $order_id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            TwigmoOrder::checkIfOrderAllowed($order_id, $_SESSION['auth'], $response);
            $object = TwigmoOrder::getOrderInfo($order_id);
            $title = 'order';

        } elseif ($meta['object'] == 'users') {

            $_auth = & $_SESSION['auth'];
            if (!empty($_auth['user_id'])) {
                $user_info_params = array(
                  'mode' => $mode,
                  'user_id' => $_auth['user_id']
                );
                $response->setData(fn_twg_get_user_info($user_info_params));
            } else {
                $response->addError('ERROR_ACCESS_DENIED', __('access_denied'));

            }

        } else {
            $response->addError('ERROR_UNKNOWN_REQUEST', __('unknown_request'));
            $response->returnResponse();
        }

        if (!empty($object)) {

            $response->setData($object);
        } elseif (!empty($title)) {
            $response->addError(
                'ERROR_OBJECT_WAS_NOT_FOUND',
                str_replace(
                    '[object]',
                    $title,
                    __(
                        'twgadmin_object_was_not_found'
                    )
                )
            );
        }

    } elseif ($meta['action'] == 'featured') {

        $items_qty = !empty($_REQUEST['items']) ? $_REQUEST['items'] :
                                                        TWG_RESPONSE_ITEMS_LIMIT;
        $params = $_REQUEST;

        if ($meta['object'] == 'products') {
            $conditions = array();

            $table = '?:products';

            if (!empty($params['product_id'])) {
                $conditions[] = db_quote('product_id != ?i', $params['product_id']);
            }

            if (!empty($params['category_id'])) {
                $table = '?:products_categories';
                $category_ids = db_get_fields(
                    "SELECT a.category_id
                     FROM ?:categories as a
                     LEFT JOIN ?:categories as b
                     ON b.category_id = ?i
                     WHERE a.id_path LIKE CONCAT(b.id_path, '/%')",
                    $params['category_id']
                );
                $conditions[] = db_quote('category_id IN (?n)', $category_ids);
            }

            $condition = implode(' AND ', $conditions);
            $product_ids = fn_twg_get_random_ids(
                $items_qty,
                'product_id',
                $table,
                $condition
            );

            if (!empty($product_ids)) {

                $search_params = array (
                    'pid' => $product_ids
                );
                $search_params = array_merge($_REQUEST, $search_params);
                list($result, $search_params) = fn_twg_api_get_products($search_params, $items_qty, $lang_code);
            }

        } elseif ($meta['object'] == 'categories') {

            $condition = '';

            if (!empty($params['category_id'])) {
                $category_path = db_get_field(
                    "SELECT id_path FROM ?:categories WHERE category_id = ?i",
                    $params['category_id']
                );

                if (!empty($category_path)) {
                    $condition = "id_path LIKE '$category_path/%'";
                }
            }

            $category_ids = fn_twg_get_random_ids(
                $items_qty,
                'category_id',
                '?:categories',
                $condition
            );

            if (!empty($category_ids)) {
                $search_params = array (
                    'cid' => $category_ids,
                    'group_by_level' => false
                );

                $search_params = array_merge($_REQUEST, $search_params);
                $result = fn_twg_api_get_categories($search_params, $lang_code);
            }

        } else {
            $response->addError(
                'ERROR_UNKNOWN_REQUEST',
                __('unknown_request')
            );
            $response->returnResponse();
        }

        if (!empty($result)) {
            $response->setResponseList($result);
        }
    } elseif ($meta['action'] == 'apply_for_vendor') {
        if (Registry::get('settings.Suppliers.apply_for_vendor') != 'Y') {
            $response->addError(
                'ERROR_UNKNOWN_REQUEST',
                __('unknown_request')
            );
            $response->returnResponse();
        }

        $data = $_REQUEST['company_data'];

        $data['timestamp'] = TIME;
        $data['status'] = 'N';
        $data['request_user_id'] = !empty($auth['user_id']) ? $auth['user_id'] : 0;

        $account_data = array();
        $account_data['fields'] =
            isset($_REQUEST['user_data']['fields'])
            ? $_REQUEST['user_data']['fields']
            : '';
        $account_data['admin_firstname'] =
            isset($_REQUEST['company_data']['admin_firstname'])
            ? $_REQUEST['company_data']['admin_firstname']
            : '';
        $account_data['admin_lastname'] =
            isset($_REQUEST['company_data']['admin_lastname'])
            ? $_REQUEST['company_data']['admin_lastname']
            : '';
        $data['request_account_data'] = serialize($account_data);

        if (empty($data['request_user_id'])) {
            $login_condition =
                empty($data['request_account_name'])
                ? ''
                : db_quote(
                    " OR user_login = ?s",
                    $data['request_account_name']
                );
            $user_account_exists = db_get_field(
                "SELECT user_id FROM ?:users WHERE email = ?s ?p",
                $data['email'],
                $login_condition
            );

            if ($user_account_exists) {
                fn_save_post_data();
                $response->addError(
                    'ERROR_FAIL_CREATE_USER',
                    __('error_user_exists')
                );
                $response->returnResponse();
            }
        }

        $result = fn_update_company($data);

        if (!$result) {
            fn_save_post_data();
            $response->addError(
                'ERROR_UNKNOWN_REQUEST',
                __('text_error_adding_request')
            );
            $response->returnResponse();
        }

        fn_set_notification(
            'N',
            __('information'),
            __('text_successful_request')
        );

        // Notify user department on the new vendor application
        Mailer::sendMail(array(
            'to' => 'default_company_users_department',
            'from' => 'default_company_users_department',
            'data' => array(
                'company_id' => $result,
                'company' => $data
            ),
            'tpl' => 'companies/apply_for_vendor_notification.tpl',
        ), 'A', Registry::get('settings.Appearance.backend_default_language'));

        unset($_SESSION['apply_for_vendor']['return_url']);
        $response->returnResponse();

    } else {
        $response->addError(
            'ERROR_UNKNOWN_REQUEST',
            __('unknown_request')
        );
    }

    $response->returnResponse();

} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && $mode == 'post') {
    if (!empty($_REQUEST['close_notice']) && $_REQUEST['close_notice'] == 1) {
        $_SESSION['twigmo_mobile_avail_notice_off'] = true;
        exit;
    }
}

function fn_twg_init_api_meta($response)
{
    // init request params
    $meta = array (
        'object' => !empty($_REQUEST['object']) ? $_REQUEST['object'] : '',
        'action' => !empty($_REQUEST['action']) ? $_REQUEST['action'] : '',
        'session_id' => !empty($_REQUEST['session_id']) ? $_REQUEST['session_id'] : '',
    );

    // set request params for the response
    $response->setMeta($meta['action'], 'action');

    if (!empty($meta['object'])) {
        $response->setMeta($meta['object'], 'object');
    }

    // init session
    if (!empty($meta['session_id'])) {
        // replace qurrent session with the restored by session id
        Session::set_id($meta['session_id']);
    }

    // start session
    fn_twg_init_api_session_data();

    $response->setMeta(Session::getId(), 'session_id');

    return $meta;
}

function fn_twg_get_api_data($response, $format, $required = true)
{
    $data = array();

    if (!empty($_REQUEST['data'])) {
        $data = ApiData::parseDocument(
            base64_decode(rawurldecode($_REQUEST['data'])),
            $format
        );
    } elseif ($required) {
        $response->addError(
            'ERROR_WRONG_DATA',
            __('twgadmin_wrong_api_data')
        );
        $response->returnResponse();
    }

    return $data;
}
