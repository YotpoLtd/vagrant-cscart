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
use Tygh\Session;
use Tygh\BlockManager\Container;
use Tygh\BlockManager\Grid;
use Tygh\BlockManager\Location;
use Tygh\BlockManager\ProductTabs;
use Tygh\BlockManager\RenderManager;
use Tygh\BlockManager\SchemesManager;
use Twigmo\Twigmo;
use Twigmo\Core\Api;
use Twigmo\Core\Functions\Lang;
use Twigmo\Core\Functions\BlockManager\TwigmoBlock;
use Twigmo\Core\Functions\Image\TwigmoImage;
use Twigmo\Core\Functions\UserAgent;
use Twigmo\Upgrade\TwigmoUpgrade;

function fn_twigmo_before_dispatch()
{
    if (!TwigmoUpgrade::addonIsUpdated() or $_SERVER['REQUEST_METHOD'] == 'POST') {
        return;
    }

    // ua device type (for desktop mode on supported device)
    $device = !empty($_SESSION['device']) ? $_SESSION['device'] : '';
    $user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if (!empty($user_agent)) {
        if ( stristr($user_agent, 'ipad')) {
            $device = 'ipad';
        } elseif (stristr($user_agent, 'iphone') || strstr($user_agent,'iphone')) {
            $device = 'iphone';
        } elseif (stristr($user_agent, 'blackberry')) {
            $device = 'blackberry';
        } elseif (stristr($user_agent, 'android')) {
            $device = 'android';
        } elseif (stristr($user_agent, 'windows phone os 7')) {
            $device = 'winphone';
        }
    }
    $_SESSION['device'] = $device;
    // /ua device type

    $exclude_dispatch = array('image.captcha');
    $agent_types = array ('unknown', 'phone', 'tablet');
    $tw_settings = Registry::get('addons.twigmo');
    $current_agent = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
    $use_mobile_template = true;

    if (!empty($_REQUEST['use_mobile_frontend'])) {
        $_SESSION['use_mobile_frontend'] = $_REQUEST['use_mobile_frontend'] == 'Y';
    }

    if (
        AREA != 'C'
        || empty($_SERVER['HTTP_USER_AGENT'])
        || !Registry::get('addons.twigmo.access_id')
        || defined('AJAX_REQUEST')
        || in_array($_REQUEST['dispatch'], $exclude_dispatch)
    ) {
        return;
    }

    $force_mobile_ua = TWG_FORCE_MOBILE_UA;
    $force_frontend_views = array('mobile', 'desktop', 'auto');
    $force_frontend_view = '';
    foreach ($force_frontend_views as $type) {
        if (isset($_REQUEST[$type])) {
            $force_frontend_view = $type;
            break;
        }
    }
    if (!empty($force_frontend_view)) {
        switch ($force_frontend_view) {
            case 'mobile':
                $_SESSION['use_mobile_frontend'] = true;
                $current_agent = $force_mobile_ua;
                break;
            case 'desktop':
                $_SESSION['use_mobile_frontend'] = false;
                $use_mobile_template = false;
                break;
            case 'auto':
                unset($_SESSION['use_mobile_frontend']);
                $force_frontend_view = '';
                break;
            default:
                unset($_SESSION['use_mobile_frontend']);
                $force_frontend_view = '';
                break;
        }
        $_SESSION['cache_mobile_frontend'] = false;
    }

    $not_use_mf = isset($_SESSION['use_mobile_frontend'])
                    && $_SESSION['use_mobile_frontend'] == false;
    $never_use_mf = !empty($tw_settings['use_mobile_frontend'])
                    && $tw_settings['use_mobile_frontend'] == 'never';

    // Custom css
    $customer_theme_rel_path = fn_get_theme_path(
        '[relative]/[theme]',
        'C',
        (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')?
            Registry::get('runtime.company_id'):
            null
        )
    );
    Registry::get('view')->assign('path_rel', $customer_theme_rel_path);

    if ($not_use_mf || $never_use_mf) {
        Registry::get('view')->assign('mobile_user_agent', true);
        Registry::get('view')->assign('tw_settings', $tw_settings);

        return;
    }

    if ($use_mobile_template && !empty($_SESSION['cache_mobile_frontend'])) {
        $use_mobile_template = $_SESSION['cache_mobile_frontend'] == true;
    }

    if ($use_mobile_template && empty($_SESSION['cache_mobile_frontend'])) {

        // Force auto or desktop mobile frontend
        $force_a_or_d_mf =
        $force_frontend_view != 'mobile'
          && empty($_SESSION['use_mobile_frontend'])
          || $force_frontend_view == 'auto';
      if ($force_a_or_d_mf) {

        // If come first time, or change user agent
        $first_or_change_ua =
          empty($_SESSION['twg_user_agent'])
          || empty($_SESSION['twg_ua'])
          || $_SESSION['twg_ua'] != $_SERVER['HTTP_USER_AGENT'];

        if ($first_or_change_ua) {
            $user_agent = UserAgent::processUa($current_agent);
            $_SESSION['twg_user_agent'] = $user_agent;
            // Raw user agent
            $_SESSION['twg_ua'] = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $user_agent = $_SESSION['twg_user_agent'];
        }

        $use_mobile_template =
          ($user_agent != 'unknown' && $tw_settings['use_mobile_frontend'] == $user_agent)
          ||
          ($user_agent != 'unknown' && $tw_settings['use_mobile_frontend'] == 'both_tablet_and_phone');
       }
        $_SESSION['cache_mobile_frontend'] = $use_mobile_template;
    } elseif (!$use_mobile_template) {
        $_SESSION['cache_mobile_frontend'] = false;
    }

    if ($use_mobile_template) {
        if (fn_twg_use_https_for_customer() && !defined('HTTPS')) {
            fn_redirect(
                Registry::get('config.https_location') . '/'
                . Registry::get('config.current_url')
            );
        }
        Registry::set('runtime.use_mobile_template', $use_mobile_template);

        list($scripts_url, $mobile_skin_path) = fn_twg_get_mobile_scripts_url(
            $tw_settings['selected_skin'],
            Registry::get('config.twg_jsurl')
        );

        // Custom css
        $custom_css_file_path = $customer_theme_rel_path
            . '/css/addons/twigmo/custom_'
            . ($tw_settings['selected_skin'] == 'default'? 'basic': $tw_settings['selected_skin'])
            . '.css';
        if (!is_file($custom_css_file_path)) {
            @touch($custom_css_file_path);
        }
        $custom_css_file_exists = is_file($custom_css_file_path);

        $view = Registry::get('view');
        $view->assign('custom_css_file_exists', $custom_css_file_exists);
        $view->assign('custom_css_file_path', $custom_css_file_path);
        $view->assign('tw_settings', $tw_settings);
        $view->assign('currency', CART_PRIMARY_CURRENCY);
        $view->assign('currency_settings', Registry::get('currencies.' . CART_PRIMARY_CURRENCY));
        $view->assign('mobile_scripts_url', $scripts_url);
        $view->assign('mobile_skin_path', $mobile_skin_path);
        $view->assign('favicon_url', TwigmoImage::getFaviconUrl());
    } else {
        return;
    }
}


function fn_twigmo_set_admin_notification()
{
    if (!Registry::get('addons.twigmo.access_id')) {
        fn_set_notification('N', __('notice'), __('twgadmin_connect_notification', array(
                           '[addon_link]' => fn_url('addons.update&addon=twigmo')
                            )), 'S');
    }
}


function fn_twigmo_dispatch_before_display()
{
    if (Registry::get('runtime.use_mobile_template')) {
        Registry::set('runtime.root_template', 'addons/twigmo/mobile_index.tpl');
        $settings = fn_twg_get_all_settings();
        Registry::get('view')->assign('twg_settings', $settings);
        Registry::get('view')->assign('json_twg_settings', json_encode($settings));
    }
}

function fn_twigmo_place_order(&$order_id, &$action = '', &$__order_status = '', &$cart = null)
{
    $twigmo_requirements_errors = Twigmo::checkRequirements();
    if (defined('DEVELOPMENT')){
        foreach ($twigmo_requirements_errors as $error) {
            fn_set_notification('W', __('notice'),  $error, 'S');
        }
    }

    if (!$access_id = Registry::get('addons.twigmo.access_id')) {
        return;
    }

    if ($action == 'save') {
        return;
    }

    $fields = array(
        'order_id',
        'total',
        'products'
    );

    $order_info = fn_get_order_info($order_id);

    if (!empty($order_info['items'])) {
        $order_info['products'] = array();

        foreach ($order_info['items'] as $product) {
            $order_info['products'][] = $product;
        }
        unset($order_info['items']);
    }

    $api_data = Api::getAsList('orders', array($order_info));

    if (empty($twigmo_requirements_errors)) {
        return fn_twg_post_request($api_data, 'orders', 'add');
    }
}

function fn_twigmo_get_shipments(&$params, &$fields_list, &$joins, &$condition, &$group)
{
    if (!empty($params['shipping_id'])) {
        $condition['twg_shipments_shipping_id'] = db_quote(' AND ?:shipments.shipping_id = ?i', $params['shipping_id']);
    }

    if (!empty($params['carrier'])) {
        $condition['twg_shipments_carrier'] = db_quote(' AND ?:shipments.carrier = ?s', $params['carrier']);
    }

    if (!empty($params['email'])) {
        $condition['twg_orders.email'] = db_quote(' AND ?:orders.email LIKE ?l', '%'.trim($params['email']).'%');
    }

    return true;
}

function fn_twigmo_get_users(&$params, &$fields, &$sortings, &$condition, &$join)
{
    // Search string condition for SQL query
    if (isset($params['q']) && fn_string_not_empty($params['q'])) {

        $params['q'] = trim($params['q']);
        if (empty($params['match'])) {
            $params['match'] = '';
        }
        if ($params['match'] == 'any') {
            $pieces = fn_explode(' ', $params['q']);
            $search_type = ' OR ';
        } elseif ($params['match'] == 'all') {
            $pieces = fn_explode(' ', $params['q']);
            $search_type = ' AND ';
        } else {
            $pieces = array($params['q']);
            $search_type = '';
        }

        foreach ($pieces as $piece) {
            if (strlen($piece) == 0) {
                continue;
            }

            $condition['twg_users_email_' . $piece] = db_quote("$search_type ?:users.email LIKE ?l", "%$piece%");
            $condition['twg_users_user_login_' . $piece] = db_quote(" OR ?:users.user_login LIKE ?l", "%$piece%");
            $condition['twg_users_fullname_' . $piece] = db_quote(
                " OR (?:users.firstname LIKE ?l OR ?:users.lastname LIKE ?l)",
                "%$piece%",
                "%$piece%"
            );
        }
    }
}

function fn_twigmo_additional_fields_in_search(
    &$params,
    &$fields,
    &$sortings,
    &$condition,
    &$join,
    &$sorting,
    &$group_by,
    &$tmp,
    &$piece
) {
    if (!empty($params['ppcode']) && $params['ppcode'] == 'Y') {
        $tmp .= db_quote(
            " OR (twg_pcinventory.product_code LIKE ?l OR products.product_code LIKE ?l)",
            "%$piece%",
            "%$piece%"
        );
    }
}

function fn_twigmo_get_products(
    &$params,
    &$fields,
    &$sortings,
    &$condition,
    &$join,
    &$sorting,
    &$group_by,
    &$lang_code
) {
    if (
        isset($params['q'])
        && fn_string_not_empty($params['q'])
        && !empty($params['ppcode'])
        && $params['ppcode'] == 'Y'
    ) {
        $join .= " LEFT JOIN ?:product_options_inventory as twg_pcinventory
                ON twg_pcinventory.product_id = products.product_id";
    }
}

function fn_twigmo_get_categories(
    &$params,
    &$join,
    &$condition,
    &$fields,
    &$group_by,
    &$sortings
) {
    if (!empty($params['depth'])) {

        if (!empty($params['category_id'])) {
            $from_id_path = db_get_field(
                "SELECT id_path FROM ?:categories WHERE category_id = ?i",
                $params['category_id']
            ) . '/';

        } else {
            $from_id_path = '';
        }

        $from_id_path .= str_repeat('%/', $params['depth']) . '%';
        $condition['twg_categories_id_path'] = db_quote(" AND NOT ?:categories.id_path LIKE ?l", "$from_id_path");
    }

    if (!empty($params['cid'])) {
        $cids = is_array($params['cid']) ? $params['cid'] : array($params['cid']);
        $condition['twg_categories_category_id'] = db_quote(" AND ?:categories.category_id IN (?n)", $cids);
    }
}

/**
 * Delete vendor's rates when placing order
 */
function fn_twigmo_order_placement_routines(
    &$order_id,
    &$force_notification,
    &$order_info,
    &$_error,
    &$is_twg_hook = false
) {
    if (
        !empty($_SESSION['companies_rates'])
        and empty($_SESSION['shipping_rates'])
        and !isset($_SESSION['shipping_hash'])
    ) {
        $_SESSION['companies_rates'] = array();
    }
}

/**
 * Delete vendor's rates when init user
 */
function fn_twigmo_user_init(&$auth, &$user_info, &$first_init)
{
    if ($first_init and !empty($_SESSION['companies_rates'])) {
        $_SESSION['companies_rates'] = array();
    }
}

/**
 * Hook - multi type filter - for twigmo should be text page or link
 */
function fn_twigmo_get_pages(
    &$params,
    &$join,
    &$condition,
    &$fields,
    &$group_by,
    &$sortings,
    &$lang_code
) {
    if (!empty($params['page_types'])) {
        $condition['twg_pages_page_type'] = db_quote(
            " AND ?:pages.page_type IN (?a)",
            $params['page_types']
        );
    }
}

/**
 * Hook - as far as we use ajax requests we cant send 302 responce - will use meta redirect
 */
function fn_twigmo_redirect_complete()
{
    if (
        isset($_REQUEST['dispatch'])
        and $_REQUEST['dispatch'] == 'twigmo.post'
        and $_SERVER['REQUEST_METHOD'] == 'POST'
        and !empty($_SESSION['cache_mobile_frontend'])
    ) {
        fn_define('META_REDIRECT', true);
    }
}

// ===========================================================================


function fn_twg_get_mobile_scripts_url($selected_skin = 'default', $url = '')
{
    $scripts_url =
        Registry::get('addons.twigmo.storefront_scripts_url')
        . Registry::get('addons.twigmo.access_id');

    return empty($url)
                    ? array($scripts_url, $scripts_url . '/skins/' . $selected_skin)
                    : array($url, $url . '/skins/' . $selected_skin);
}

// Check if twigmo front end should uses https
function fn_twg_use_https_for_customer()
{
    if (Registry::get('settings.General.keep_https') != 'Y') {
        return false;
    }

    if (TWIGMO_USE_HTTPS == 'Y') {
        return true;
    }
    if (TWIGMO_USE_HTTPS == 'N') {
        return false;
    }
    // Auto
    return (Registry::get('settings.General.secure_auth') == 'Y'
    || Registry::get('settings.General.secure_checkout') == 'Y');
}



/**
 * Create new file and put serialized data here
 * @param string $file
 * @param array $data
 * @param boolean $serialize
 */
function fn_twg_write_to_file($file, $data, $serialize = true)
{
    $dir = dirname($file);
    if (!file_exists($dir)) {
        fn_mkdir($dir);
    }
    $file = fopen($file, 'w');
    fwrite($file, $serialize ? serialize($data) : $data);
    fclose($file);
}

function fn_twg_save_version_info($version_info)
{
    fn_twg_write_to_file(TWIGMO_UPGRADE_DIR . TWIGMO_UPGRADE_VERSION_FILE, $version_info);
}



function fn_twg_post_request($api_data, $object_type, $action)
{
    $twigmo = fn_twg_init_twigmo();

    if ($object_type != 'orders') {
        return true;
    }

    $result = $twigmo->postData($api_data, $object_type, $action, array('dispatch' => 'api.post'));

    if ($result) {
        return true;
    }

    if (empty($twigmo->response_data['error'])) {
        if (AREA == 'A') {
            fn_set_notification('E', __('error'), __('twg_post_request_fail'), true);
        }

        return false;
    }

    $errors = array();
    foreach ($twigmo->getObjects($twigmo->response_data['error']) as $error) {
        $errors[] = $error['message'];
    }

    if (AREA == 'A') {
        fn_set_notification(
            'E',
            __('error'),
            __('twg_post_request_fail')
            . ' "'
            . implode('", "', $errors) . '"', true
        );
    }

    return false;
}

function fn_twg_get_user_search_params()
{
    $twigmo_params = array (
        'page',
        'company',
        'user_name',
        'user_login',
        'tax_exempt',
        'status',
        'email',
        'address',
        'zipcode',
        'country',
        'state',
        'city',
        'user_type',
        'user_id',
        'exclude_user_types',
        'sort_order',
        'sort_by'
    );

    $result = array();
    foreach ($twigmo_params as $param) {
        if (!empty($_REQUEST[$param])) {
            $result[$param] = $_REQUEST[$param];
        }
    }

    return $result;
}

function fn_twg_init_twigmo()
{
    return new Twigmo(
        TWIGMO_SERVICE_URL,
        Registry::get('addons.twigmo.access_id'),
        Registry::get('addons.twigmo.secret_access_key'),
        Registry::get('addons.twigmo.service_username'),
        Registry::get('addons.twigmo.service_password')
    );
}

function fn_twg_allow_request()
{
    switch ($_REQUEST['action']) {
        case 'login':
            $allow = true;
            break;
        case 'logout':
            $allow = true;
            break;
        case 'update':
            $allow = ($_REQUEST['object'] == 'cart');
            break;
        case 'get':
            $allow = (
                ($_REQUEST['object'] == 'shipping_methods')
                || ($_REQUEST['object'] == 'payment_methods')
            );
            break;
        case 'details':
            $allow = (($_REQUEST['object'] == 'orders') || ($_REQUEST['object'] == 'users'));
            break;
        case 'place_order':
            $allow = true;
            break;
        case 'edit_css':
            $allow = true;
            break;
        default:
            $allow = false;
    }

    return $allow;
}

function fn_twg_validate_auth()
{
    return (
        Twigmo::validateAuth(Registry::get('addons.twigmo.secret_access_key'))
        || fn_twg_allow_request()
    );
}

function fn_twg_api_update_product($product_data, $product_id = 0, $lang_code = CART_LANGUAGE)
{
    $_data = $product_data;
    if (!empty($product_data['timestamp'])) {
        $_data['timestamp'] = fn_parse_date($product_data['timestamp']); // Minimal data for product record
    }
    if (!empty($product_data['avail_since'])) {
        $_data['avail_since'] = fn_parse_date($product_data['avail_since']);
    }
    if (Registry::get('settings.General.allow_negative_amount') == 'N' && isset($_data['amount'])) {
        $_data['amount'] = abs($_data['amount']);
    }
    // add new product
    if (empty($product_id)) {
        $create = true;
        // product title can't be empty
        if (empty($product_data['product'])) {
            return false;
        }
        $product_id = db_query("INSERT INTO ?:products ?e", $_data);
        if (empty($product_id)) {
            return false;
        }
        //
        // Adding same product descriptions for all cart languages
        //
        $_data = $product_data;
        $_data['product_id'] =    $product_id;
        $_data['product'] = trim($_data['product'], " -");
        $langs = (array) Registry::get('languages');
        foreach (array_keys($langs) as $_data['lang_code']) {
            db_query("INSERT INTO ?:product_descriptions ?e", $_data);
        }
    // update product
    } else {
        if (isset($product_data['product']) && empty($product_data['product'])) {
            unset($product_data['product']);
        }

        db_query("UPDATE ?:products SET ?u WHERE product_id = ?i", $_data, $product_id);

        $_data = $product_data;
        if (!empty($_data['product'])) {
            $_data['product'] = trim($_data['product'], " -");
        }
        db_query(
            "UPDATE ?:product_descriptions SET ?u WHERE product_id = ?i AND lang_code = ?s",
            $_data,
            $product_id,
            $lang_code
        );
    }
    // Log product add/update
    fn_log_event('products', !empty($create) ? 'create' : 'update', array(
        'product_id' => $product_id
    ));
    // Update product prices
    if (isset($product_data['price'])) {
        if (!isset($product_data['prices'])) {
            $product_data['prices'] = array();
            $skip_price_delete = true;
        }
        $_price = array (
            'price' => abs($product_data['price']),
            'lower_limit' => 1,
        );

        array_unshift($product_data['prices'], $_price);
    }
    if (!empty($product_data['prices'])) {
        if (empty($skip_price_delete)) {
            db_query("DELETE FROM ?:product_prices WHERE product_id = ?i", $product_id);
        }
        foreach ($product_data['prices'] as $v) {
            if (!empty($v['lower_limit'])) {
                $v['product_id'] = $product_id;
                db_query("REPLACE INTO ?:product_prices ?e", $v);
            }
        }
    }
    // Update main icon
    if (!empty($product_data['icon'])) {
        TwigmoImage::updateIconsByApiData($product_data['icon'], $product_id);
    }
    // Update additional images
    if (!empty($product_data['images'])) {
        TwigmoImage::updateImagesByApiData($product_data['images'], $product_id);
    }

    return $product_id;
}

function fn_twg_get_carriers()
{
    return array (
        'USP'=> __('usps'),
        'UPS'=> __('ups'),
        'FDX'=> __('fedex'),
        'AUP'=> __('australia_post'),
        'DHL'=> __('dhl'),
        'CHP'=> __('chp')
    );
}

function fn_twg_get_domain_name($host)
{
    $parts = explode('.', $host);
    array_pop($parts); // remove 1st-level domain
    $domain = array_pop($parts); // get 2nd-level domain

    return $domain;
}

/**
 * Get products as API list
 * @param array $params
 * @param integer $items_per_page
 * @param string $lang_code
 * @return array array('products' => array(), 'params' => array())
 */
function fn_twg_api_get_products($params, $items_per_page, $lang_code = CART_LANGUAGE)
{

    if (empty($params['extend'])) {
        $params['extend'] = array (
            'description'
        );
    }

    if (!empty($params['pid']) && !is_array($params['pid'])) {
        $params['pid'] = explode(',', $params['pid']);
    }

    if (!empty($params['q'])) {
        // search by product code
        $params['ppcode'] = 'Y';
        $params['subcats'] = 'Y';
        $params['status'] = 'A';
        $params['pshort'] = 'Y';
        $params['pfull'] = 'Y';
        $params['pname'] = 'Y';
        $params['pkeywords'] = 'Y';
        $params['search_performed'] = 'Y';
    }

    if (isset($params['company_id']) and $params['company_id'] == 0) {
        unset($params['company_id']);
    }

    list($products, $params) = fn_get_products($params, $items_per_page, $lang_code);

    fn_gather_additional_products_data(
        $products,
        array(
            'get_icon' => true,
            'get_detailed' => true,
            'get_options' => true,
            'get_discounts' => true,
            'get_features' => false
        )
    );

    if (empty($products)) {
        return false;
    }

    $product_ids = array();
    $image_params = Registry::get('addons.twigmo.catalog_image_size');
    foreach ($products  as $k => $v) {

        if (!empty($products[$k]['short_description']) || !empty($products[$k]['full_description'])) {
            $products[$k]['short_description'] = !empty($products[$k]['short_description']) ?
                strip_tags($products[$k]['short_description']) :
                fn_substr(strip_tags($products[$k]['full_description']), 0, TWG_MAX_DESCRIPTION_LEN);
            unset($products[$k]['full_description']);
        } else {
            $products[$k]['short_description'] = '';
        }

        $product_ids[] = $v['product_id'];

        // Get product image data
        if (!empty($v['main_pair'])) {
            $products[$k]['icon'] = TwigmoImage::getApiImageData($v['main_pair'], 'product', 'icon', $image_params);
        }

    }

    $category_descr = !empty($product_ids)?
        db_get_hash_array(
            "SELECT p.product_id, p.category_id, c.category
             FROM ?:products_categories AS p, ?:category_descriptions AS c
             WHERE c.category_id = p.category_id
             AND c.lang_code = ?s
             AND p.product_id IN (?a)
             AND p.link_type = 'M'",
            'product_id',
            $lang_code,
            $product_ids
        ):
        array();

    foreach ($products as $key => $product) {
        if (!empty($product['product_id']) &&
            !empty($category_descr[$product['product_id']])
        ) {
            $products[$key]['category'] = $category_descr[$product['product_id']]['category'];
            $products[$key]['category_id'] = $category_descr[$product['product_id']]['category_id'];
        }
        if (!empty($product['inventory_amount']) &&
            $product['inventory_amount'] > $product['amount']
        ) {
            $products[$key]['amount'] = $product['inventory_amount'];
        }
    }

    $result = Api::getAsList('products', $products);

    return array($result, $params);
}

function fn_twg_api_get_categories($params, $lang_code = CART_LANGUAGE)
{
    $params['get_images'] = 'Y';
    $category_id = !empty($params['id']) ? $params['id'] : 0;
    $type = !empty($params['type']) ? $params['type'] : '';

    if ($type == 'one_level') {
        $type_params = array (
            'category_id' => $category_id,
            'current_category_id' => $category_id,
            'simple' => false,
            'visible' => true
        );

    } elseif ($type == 'plain_tree') {
        $type_params = array (
            'category_id' => $category_id,
            'current_category_id' => $category_id,
            'simple' => false,
            'visible' => false,
            'plain' => true
        );

    } else {
        $type_params = array (
            'simple' => false,
            'category_id' => $category_id,
            'current_category_id' => $category_id
        );
    }
    $params =  array_merge($type_params, $params);

    list($categories, ) = fn_get_categories($params, $lang_code);

    $image_params = Registry::get('addons.twigmo.catalog_image_size');
    foreach ($categories as $k => $v) {
        if (!empty($v['has_children'])) {
            $categories[$k]['subcategory_count'] = db_get_field(
                "SELECT COUNT(*) FROM ?:categories WHERE parent_id = ?i",
                $v['category_id']
            );
        }
        if (!empty($params['get_images']) && !empty($v['main_pair'])) {
            $categories[$k]['icon'] = TwigmoImage::getApiImageData(
                $v['main_pair'],
                'category',
                'icon',
                $image_params
            );
        }
    }

    $result = Api::getAsList('categories', $categories);

    return $result;
}

function fn_twg_api_get_product_options($product, $lang_code = CART_LANGUAGE)
{
    $condition = $_status = $join = '';
    $extra_variant_fields = '';
    $option_ids = $variants_ids = $options = array();
    $_status .= " AND status = 'A'";
    $product_ids = $product['product_id'];

    $join = db_quote(
        " LEFT JOIN ?:product_options_descriptions as b
         ON a.option_id = b.option_id AND b.lang_code = ?s ",
        $lang_code
    );
    $fields = "a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment";

    if (!empty($product_ids)) {
        $_options = db_get_hash_multi_array(
            "SELECT " . $fields
            . " FROM ?:product_options as a "
            . $join
            . " WHERE a.product_id IN (?n)" . $condition . $_status
            . " ORDER BY a.position",
            array('product_id', 'option_id'), $product_ids
        );

        $fields = "c.product_id AS cur_product_id, a.*, "
        . "b.option_name, b.option_text, b.description, b.inner_hint, "
        . "b.incorrect_message, b.comment";
        $global_options = db_get_hash_multi_array(
            "SELECT $fields"
            . " FROM ?:product_options as a"
            . " LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s"
            . " LEFT JOIN ?:product_global_option_links as c ON c.option_id = a.option_id"
            . " WHERE c.product_id IN (?n) AND a.product_id = 0" . $condition . $_status
            . " ORDER BY a.position",
            array('cur_product_id', 'option_id'),
            $lang_code,
            $product_ids
        );

        foreach ((array) $product_ids as $product_id) {
            $_opts = (
                    empty($_options[$product_id]) ?
                    array() :
                    $_options[$product_id]
                ) + (
                    empty($global_options[$product_id]) ?
                    array() :
                    $global_options[$product_id]
                );
            $options[$product_id] = fn_sort_array_by_key($_opts, 'position');
        }
    } else {
        //we need a separate query for global options
        $options = db_get_hash_multi_array(
            "SELECT a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment"
            . " FROM ?:product_options as a"
            . $join
            . " WHERE a.product_id = 0" . $condition . $_status
            . " ORDER BY a.position",
            array('product_id', 'option_id')
        );
    }

    foreach ($options as $product_id => $_options) {
        $option_ids = array_merge($option_ids, array_keys($_options));
    }

    if (empty($option_ids)) {
        if (is_array($product_ids)) {
            return $options;
        } else {
            return !empty($options[$product_ids]) ? $options[$product_ids] : array();
        }
    }

    $_status = " AND a.status='A'";

    $v_fields = "a.variant_id, a.option_id, a.position, a.modifier, "
    . "a.modifier_type, a.weight_modifier, a.weight_modifier_type, "
    . "$extra_variant_fields b.variant_name";
    $v_join = db_quote(
        "LEFT JOIN ?:product_option_variants_descriptions as b
         ON a.variant_id = b.variant_id
         AND b.lang_code = ?s",
        $lang_code
    );
    $v_condition = db_quote(
        "a.option_id IN (?n) $_status",
        array_unique($option_ids)
    );
    $v_sorting = "a.position, a.variant_id";
    $variants = db_get_hash_multi_array(
        "SELECT $v_fields FROM ?:product_option_variants as a $v_join WHERE $v_condition ORDER BY $v_sorting",
        array('option_id', 'variant_id')
    );

    foreach ($variants as $option_id => $_variants) {
        $variants_ids = array_merge($variants_ids, array_keys($_variants));
    }

    if (isset($variants_ids) && empty($variants_ids)) {
        return is_array($product_ids)? $options: $options[$product_ids];
    }

    $image_pairs = fn_get_image_pairs(array_unique($variants_ids), 'variant_image', 'V', true, true, $lang_code);

    foreach ($variants as $option_id => &$_variants) {
        foreach ($_variants as $variant_id => &$_variant) {
            $_variant['image_pair'] =
            !empty($image_pairs[$variant_id]) ?
                reset($image_pairs[$variant_id]) :
                array();
        }
    }

    foreach ($options as $product_id => &$_options) {
        foreach ($_options as $option_id => &$_option) {
            // Add variant names manually, if this option is "checkbox"
            if ($_option['option_type'] == 'C' && !empty($variants[$option_id])) {
                foreach ($variants[$option_id] as $variant_id => $variant) {
                    $variants[$option_id][$variant_id]['variant_name'] =
                    $variant['position'] == 0 ?
                        __('no') :
                        __('yes');
                }
            }

            $_option['variants'] = !empty($variants[$option_id])? $variants[$option_id] : array();
        }
    }

    return is_array($product_ids)? $options: $options[$product_ids];
}

function fn_twg_get_api_product_data($product_id, $lang_code = CART_LANGUAGE)
{
    $auth = & $_SESSION['auth'];

    $product = fn_get_product_data($product_id, $auth, $lang_code, '', true, true, true, true, true);

    if (empty($product)) {
        return array();
    }

    fn_gather_additional_product_data($product, true, true);

    // Delete empty product feature groups
    foreach ($product['product_features'] as $feature_id => $feature) {
        if ($feature['feature_type'] == 'G' and empty($feature['subfeatures'])) {
            unset($product['product_features'][$feature_id]);
        }
    }

    $product['product_options'] = array();
    $product_options = fn_twg_api_get_product_options($product);
    if (!empty($product['combination'])) {
        $selected_options = fn_get_product_options_by_combination($product['combination']);
        $product['product_options'] =
            !empty($selected_options)?
                fn_get_selected_product_options(
                    $product['product_id'],
                    $selected_options,
                    $lang_code
                ):
                $product_options[$product_id];
    }
    $product['product_options'] = $product_options;
    foreach ($product['product_options'] as $key1 => $val1) {
        $option_descriptions = db_get_row(
            "SELECT option_name, option_text, description, comment
             FROM ?:product_options_descriptions
             WHERE option_id = ?i AND lang_code = ?s",
            $val1['option_id'],
            $lang_code
        );
        foreach ($option_descriptions as $key2 => $val2) {
            $product['product_options'][$key1][$key2] = $val2;
        }
        $val1['variants'] = isset($val1['variants']) ? $val1['variants'] : array();
        foreach (array_keys($val1['variants']) as $vid) {
            if ($val1['option_type'] == 'C') {
                $product['product_options'][$key1]['variants'][$vid]['variant_name'] =
                    empty($val1['position'])?
                        __('no', $lang_code):
                        __('yes', $lang_code);
            } elseif ($val1['option_type'] == 'S' || $val1['option_type'] == 'R') {
                $variant_description = db_get_field(
                    "SELECT variant_name
                     FROM ?:product_option_variants_descriptions
                     WHERE variant_id = ?i AND lang_code = ?s",
                    $vid,
                    $lang_code
                );
                $product['product_options'][$key1]['variants'][$vid]['variant_name'] = $variant_description;
            }
        }
    }

    $product['category_id'] = $product['main_category'];
    $product['images'] = array();

    $image_params = Registry::get('addons.twigmo.big_image_size');
    $image_params['keep_small_images'] = true;
    if (!empty($product['main_pair'])) {
        $product['icon'] = TwigmoImage::getApiImageData(
            $product['main_pair'],
            'product',
            'icon',
            Registry::get('addons.twigmo.prewiew_image_size')
        );
        $product['images'][] = TwigmoImage::getApiImageData(
            $product['main_pair'],
            'product',
            'detailed',
            $image_params
        );
    }

    foreach ($product['image_pairs'] as $v) {
        $product['images'][] = TwigmoImage::getApiImageData($v, 'product', 'detailed', $image_params);
    }

    $product['category'] = db_get_field(
        "SELECT category FROM ?:category_descriptions WHERE category_id = ?i AND lang_code = ?s",
        $product['main_category'],
        $lang_code
    );

    $product['product_options_exceptions'] = fn_twg_get_api_product_options_exceptions($product_id);
    $product['product_options_inventory'] = fn_twg_get_api_product_options_inventory($product_id, $lang_code);

    $_product = Api::getAsApiObject('products', $product);

    $_product['avail_since_formated'] = strftime(
        Registry::get('settings.Appearance.date_format'),
        $_product['avail_since']
    );
    $_product['TIME'] = TIME;
    $_product['tabs'] = fn_twg_get_product_tabs(array('product_id' => $product_id, 'descr_sl' => DESCR_SL));

    $_product['default_image'] = fn_get_image_pairs($product_id, 'product', 'M', true, true);

  return $_product;
}

function fn_twg_get_product_tabs($params)
{
    $allowed_page_types = array('T', 'L', 'F');
    $allowed_templates = array('blocks/product_tabs/features.tpl', 'blocks/product_tabs/description.tpl');
    $not_allowed_block_types = array('cart_content', 'template');
    $tabs = ProductTabs::instance()->getList('', $params['product_id'], $params['descr_sl']);
    foreach ($tabs as $k => $tab) {
        $isAllowedType = $tab['tab_type'] == 'B';
        $isAllowedType = ($isAllowedType or $tab['tab_type'] == 'T' and in_array($tab['template'], $allowed_templates));
        if ((empty($params['all_tabs']) && $tab['status'] != 'A') || !$isAllowedType) {
            unset($tabs[$k]);
            continue;
        }

        if ($tab['tab_type'] == 'B') {
            $block = TwigmoBlock::getBlock(array('block_id' => $tab['block_id'], 'area' => 'C'));
            if (in_array($block['type'], $not_allowed_block_types)) {
                unset($tabs[$k]);
                continue;
            }
            $block_scheme = SchemesManager::getBlockScheme($block['type'], array());
            if ($block['type'] == 'html_block') {
                $tabs[$k]['html'] = $block['content']['content'];
            } elseif (!empty($block_scheme['content']) && !empty($block_scheme['content']['items'])) {
                // Products and categories: get items
                $template_variable = 'items';
                $field = $block_scheme['content']['items'];
                $items = RenderManager::getValue($template_variable, $field, $block_scheme, $block);
                // Filter pages - only texts, links and forms posible
                if ($block['type'] == 'pages') {
                    foreach ($items as $item_id => $item) {
                        if (!in_array($item['page_type'], $allowed_page_types)) {
                            unset($items[$item_id]);
                        }
                    }
                }
                if (fn_is_empty($items)) {
                    unset($tabs[$k]);
                    continue;
                }
                $block_data = array('total_items' => count($items));
                // Images
                $image_params = Registry::get('addons.twigmo.cart_image_size');
                if ($block['type'] == 'products' or $block['type'] == 'categories') {
                    $object_type = $block['type'] == 'products' ? 'product' : 'category';
                    foreach ($items as $items_id => $item) {
                        $main_pair = fn_get_image_pairs($item[$object_type . '_id'], $object_type, 'M', true, true);
                        if (!empty($main_pair)) {
                            $items[$items_id]['icon'] = TwigmoImage::getApiImageData($main_pair, $object_type, 'icon', $image_params);
                        }
                    }
                }
                // Banners properties
                if ($block['type'] == 'banners') {
                    $rotation = $block['properties']['template'] == 'addons/banners/blocks/carousel.tpl' ? 'Y' : 'N';
                    $block_data['delay'] = $rotation == 'Y' ? $block['properties']['delay'] : 0;
                    $object_type = 'banner';
                }
                $block_data[$block['type']] = Api::getAsList($block['type'], $items);
                $tabs[$k] = array_merge($tab, $block_data);
            }
        } else {
            if ($tab['template'] == 'blocks/product_tabs/features.tpl') {
                $tabs[$k]['type'] = 'features';
            }
            if ($tab['template'] == 'blocks/product_tabs/description.tpl') {
                $tabs[$k]['type'] = 'description';
            }
        }
    }

    $tabs[] = array(
        'type'  => 'share',
        'name' => fn_get_lang_var('twg_share')
    );

    return array_values($tabs); // reindex
}

function fn_twg_get_api_category_data($category_id, $lang_code)
{
    $category = fn_get_category_data($category_id, $lang_code);

    if (!empty($category['parent_id'])) {
        $category['parent_category'] = db_get_field(
            "SELECT category
             FROM ?:category_descriptions
             WHERE ?:category_descriptions.category_id = ?i
             AND ?:category_descriptions.lang_code = ?s",
            $category['parent_id'],
            $lang_code
        );
    }
    if (!empty($category['main_pair'])) {
        $category['icon'] = TwigmoImage::getApiImageData(
            $category['main_pair'],
            'category',
            'detailed',
            $_REQUEST
        );
    }

    return Api::getAsApiObject('categories', $category);
}

function fn_twg_get_api_product_options_exceptions($product_id)
{
    $mode = fn_get_storage_data('store_mode');

    if ($mode == 'free') {
        return array();
    }

    $exceptions = db_get_array(
        "SELECT *
         FROM ?:product_options_exceptions
         WHERE product_id = ?i
         ORDER BY exception_id",
        $product_id
    );

    if (empty($exceptions)) {
        return array();
    }

    foreach ($exceptions as $k => $v) {
        $_comb = unserialize($v['combination']);
        $exceptions[$k]['combination'] = array();

        foreach ($_comb as $option_id => $variant_id) {
            $exceptions[$k]['combination'][] = array (
                'option_id' => $option_id,
                'variant_id' => $variant_id
            );
        }
    }

    return $exceptions;
}

function fn_twg_get_api_product_options_inventory($product_id, $lang_code = CART_LANGUAGE)
{
    $inventory = db_get_array(
        "SELECT *
         FROM ?:product_options_inventory
         WHERE product_id = ?i
         ORDER BY position",
        $product_id
    );

    if (empty($inventory)) {
        return array();
    }

    $inventory_ids = array();
    foreach ($inventory as $k => $v) {
        $inventory_ids[] = $v['combination_hash'];
    }

    $image_pairs = fn_get_image_pairs(
        $inventory_ids,
        'product_option',
        'M',
        false,
        true,
        $lang_code
    );

    foreach ($inventory as $k => $v) {
        $inventory[$k]['combination'] = array();
        $_comb = fn_get_product_options_by_combination($v['combination']);

        if (!empty($image_pairs[$v['combination_hash']])) {
            $inventory[$k]['image'] = TwigmoImage::getApiImageData(
                current($image_pairs[$v['combination_hash']]),
                'product_option',
                'detailed',
                $_REQUEST
            );
        }

        foreach ($_comb as $option_id => $variant_id) {
            $inventory[$k]['combination'][] = array (
                'option_id' => $option_id,
                'variant_id' => $variant_id
            );
        }
    }

    return $inventory;
}

function fn_twg_api_customer_login($user_login, $password)
{
    list($status, $user_data, $user_login, $password, $salt) =
        fn_twg_api_auth_routines($user_login, $password);

    if ($status === false) {
        return false;
    }

    if (
        empty($user_data)
        || (fn_generate_salted_password($password, $salt) != $user_data['password'])
        || empty($password)
    ) {

        fn_log_event(
            'users',
            'failed_login',
            array ('user' => $user_login)
        );

        return false;
    }

    $_SESSION['auth'] = fn_fill_auth($user_data);

    // Set last login time
    db_query(
        "UPDATE ?:users SET ?u WHERE user_id = ?i",
        array('last_login' => TIME),
        $user_data['user_id']
    );

    $_SESSION['auth']['this_login'] = TIME;
    $_SESSION['auth']['ip'] = $_SERVER['REMOTE_ADDR'];

    // Log user successful login
    fn_log_event('users', 'session', array(
        'user_id' => $user_data['user_id']
    ));

    if ($cu_id = fn_get_session_data('cu_id')) {
        $cart = array();
        fn_clear_cart($cart);
        fn_save_cart_content($cart, $cu_id, 'C', 'U');
        fn_delete_session_data('cu_id');
    }

    fn_init_user_session_data($_SESSION, $user_data['user_id']);

    return $user_data;
}

function fn_twg_api_customer_logout()
{
    // copied from common/auth.php - logout mode
    $auth = $_SESSION['auth'];

    fn_save_cart_content($_SESSION['cart'], $auth['user_id']);

    if (!empty($auth['user_id'])) {
        // Log user logout
        fn_log_event('users', 'session', array(
            'user_id' => $auth['user_id'],
            'time' => TIME - $auth['this_login'],
            'timeout' => false
        ));
    }

    unset($_SESSION['auth']);
    fn_clear_cart($_SESSION['cart'], false, true);

    fn_delete_session_data(fn_get_area_name() . '_user_id', fn_get_area_name() . '_password');

    return true;
}

/*
 * Copy of fn_auth_routines
 * from auth.php
 */
function fn_twg_api_auth_routines($user_login, $password)
{
    $status = true;

    $field = Registry::get('settings.General.use_email_as_login') == 'Y' ?
        'email' :
        'user_login';

    $condition = '';

    if (fn_allowed_for('ULTIMATE')) {
        if (Registry::get('settings.Stores.share_users') == 'N' && AREA != 'A') {
            $condition = fn_get_company_condition('?:users.company_id');
        }
    }

    $user_data = db_get_row(
        "SELECT *
         FROM ?:users
         WHERE $field = ?s" . $condition,
        $user_login
    );

    if (empty($user_data)) {
        $user_data = db_get_row(
            "SELECT *
             FROM ?:users
             WHERE $field = ?s AND user_type IN ('A', 'V', 'P')",
            $user_login
        );
    }

    if (!empty($user_data)) {
        $user_data['usergroups'] = fn_get_user_usergroups($user_data['user_id']);
    }

    if (
        !empty($user_data)
        && (
            !fn_check_user_type_admin_area($user_data)
            && AREA == 'A'
            || !fn_check_user_type_access_rules($user_data)
            )
    ) {
        fn_set_notification(
            'E',
            __('error'),
            __('error_area_access_denied')
        );
        $status = false;
    }

    if (!empty($user_data['status']) && $user_data['status'] == 'D') {
        fn_set_notification(
            'E',
            __('error'),
            __('error_account_disabled')
        );
        $status = false;
    }

    $salt = isset($user_data['salt']) ? $user_data['salt'] : '';

    return array($status, $user_data, $user_login, $password, $salt);
}

/**
 * Copy of the fn_start_payment - to change MODE to place_order
 *
 * @param array $payment payment data
 * @param int $order_id order ID
 * @param bool $force_notification force user notification
 *              (true - notify, false - do not notify, order status properties will be skipped)
 */
function fn_twg_start_payment($order_id, $force_notification = array(), $payment_info)
{
    $order_info = fn_get_order_info($order_id);

    if (!empty($order_info['payment_info']) && !empty($payment_info)) {
        $order_info['payment_info'] = $payment_info;
    }

    list($is_processor_script, $processor_data) = fn_check_processor_script(
        $order_info['payment_id'],
        ''
    );
    if ($is_processor_script) {
        set_time_limit(300);
        $idata = array (
            'order_id' => $order_id,
            'type' => 'S',
            'data' => TIME,
        );
        db_query("REPLACE INTO ?:order_data ?e", $idata);

        $index_script = Registry::get('config.admin_index');
        $mode = 'place_order'; // Change mode from 'post' to 'place_order'

        include(Registry::get('config.dir.payments') . $processor_data['processor_script']);

        return fn_finish_payment($order_id, $pp_response, $force_notification);
    }

    return false;
}

function fn_twg_init_api_session_data()
{
    Session::setParams();
    Session::setHandlers();
    @Session::start();
    register_shutdown_function('session_write_close');

    // init session data
    fn_init_user();
}

function fn_twg_cmp_products($products1, $products2)
{
    if ($products1[0]['product'] == $products2[0]['product']) {
        return 0;
    }

    return ($products1[0]['product'] < $products2[0]['product']) ? -1 : 1;
}

function fn_twg_cmp_products2($product1, $product2)
{
    if ($product1['cart_id'] == $product2['cart_id']) {
        return 0;
    }

    return ($product1['cart_id'] < $product2['cart_id']) ? -1 : 1;
}

function fn_twg_filter_payment_buttons($payment_buttons)
{
    if (!$payment_buttons) {
        return $payment_buttons;
    }
    $checkout_processors = Registry::get('addons.twigmo.checkout_processors');
    $payment_btns_procs = db_get_hash_single_array(
        'SELECT p.payment_id, pp.processor
         FROM ?:payments as p
         INNER JOIN ?:payment_processors as pp
         ON pp.processor_id=p.processor_id
         WHERE p.payment_id IN (?n)',
        array('payment_id', 'processor'),
        array_keys($payment_buttons)
    );
    foreach ($payment_buttons as $payment_id => $button) {
        if (!isset($payment_btns_procs[$payment_id])
            or !in_array($payment_btns_procs[$payment_id], $checkout_processors)) {
            unset($payment_buttons[$payment_id]);
            continue;
        }
        $button = str_replace('<form ', '<form data-ajax="false" ', $button);
        // Delete bad tags for button
        $payment_buttons[$payment_id] = trim(
            str_replace(
                array(
                    '<html>',
                    '<body>',
                    '</body>',
                    '</html>',
                    '<br/>'
                ),
                '',
                $button
            )
        );
    }

    return array_values($payment_buttons);
}

function fn_twg_api_get_cart_promotion_input_field()
{
    $input_field = 'N';
    if (fn_display_promotion_input_field($_SESSION['cart'])) {
        $input_field = Registry::get('addons.gift_certificates.status') == 'A' ? 'G' : 'P';
    }

    return $input_field;
}

function fn_twg_get_payment_methods()
{
    $payment_groups = fn_prepare_checkout_payment_methods($_SESSION['cart'], $_SESSION['auth']);
    if (!$payment_groups) {
        $payment_groups = array();
    }
    $payment_methods = array();
    foreach ($payment_groups as $payment_group) {
        $payment_methods = array_merge_recursive($payment_methods, $payment_group);
    }
    // unset FRIbetaling payment
    foreach ($payment_methods as $key => $payment_method) {
        if (isset($payment_method['processor']) and $payment_method['processor'] == 'FRIbetaling') {
            unset($payment_methods[$key]);
        }
    }

    return Api::getAsList('payments', $payment_methods);
}

function fn_twg_api_get_session_cart(&$cart, $lang_code = CART_LANGUAGE)
{
    $data = array('amount' => 0, 'subtotal' => 0);
    $auth = $_SESSION['auth'];
    if (empty($cart)) {
        return $data;
    }

    $payment_methods = fn_twg_get_payment_methods();
    if (isset($payment_methods['payment'])) {
        $payment_methods = $payment_methods['payment'];
    }

    if (false !=($first_method = reset($payment_methods)) && empty($cart['payment_id']) && isset($cart['total']) && floatval($cart['total']) != 0) {
        $cart['payment_id'] = $first_method['payment_id'];
    }
    if (isset($cart['total']) && floatval($cart['total']) == 0) {
        $cart['payment_id'] = 0;
    }

    // fetch cart data
    $cart_data = array_merge($data, Api::getAsApiObject('cart', $cart, array(), array('lang_code' => $lang_code)));
    if (!empty($cart_data['taxes'])) {
        $cart_data['taxes'] = Api::getAsList('taxes', $cart_data['taxes']);
    }
    if (!empty($cart_data['products'])) {
        $cart_data['products'] = array_reverse($cart_data['products']);
    }

    if (!empty($cart_data['payment_surcharge'])) {
        $cart_data['total'] += $cart_data['payment_surcharge'];
    } else {
        $cart_data['payment_surcharge'] = 0;
    }

    // ================ Payment buttons ========================================
    $payment_buttons = array();
    $checkout_processors = array('amazon_checkout.php', 'paypal_express.php', 'google_checkout.php');
    $included_files = get_included_files();
    $is_payments_included = false;
    foreach ($checkout_processors as $checkout_processor) {
        if (in_array(Registry::get('config.dir.payments') . $checkout_processor, $included_files)) {
            $is_payments_included = true;
            break;
        }
    }
    if ($is_payments_included) {
        // Get from templater
        $view = Registry::get('view');
        $smarty_vars = array('checkout_add_buttons', 'checkout_buttons');
        foreach ($smarty_vars as $smarty_var) {
            $buttons = $view->getTemplateVars($smarty_var);
            if ($buttons !== null) {
                $payment_buttons = $buttons;
                break;
            }
        }
    } else {
        // Get payments fiels
        if (!empty($cart['products'])) {
            foreach ($cart['products'] as $product_key => $product) {
                if (!isset($cart['products'][$product_key]['product'])) {
                    $product_data = fn_get_product_data($product['product_id'], $auth);
                    $cart['products'][$product_key]['product'] =
                    $product_data['product'];
                    $cart['products'][$product_key]['short_description'] =
                    $product_data['short_description'];
                }

            }
            $mode = Registry::get('runtime.mode');
            Registry::set('runtime.mode', 'cart'); # for the paypal express checkout
            $payment_buttons = fn_get_checkout_payment_buttons($cart, $cart['products'], $auth);
            Registry::set('runtime.mode', $mode);
        }
    }
    $cart_data['payment_buttons'] = fn_twg_filter_payment_buttons($payment_buttons);
    // ================ /Payment buttons =======================================

    $cart_data['empty_payments'] = empty($payment_methods) ? 'Y' : 'N';
    $cart_data['coupons'] = empty($cart['coupons']) ? array() : array_keys($cart['coupons']);
    $cart_data['use_gift_certificates'] = array();
    $cart_data['gift_certificates_total'] = 0;
    if (isset($cart['use_gift_certificates'])) {
        foreach ($cart['use_gift_certificates'] as $code => $cert) {
            $cart_data['use_gift_certificates'][] = array('code' => $code, 'cost' => $cert['cost']);
            $cart_data['gift_certificates_total'] += $cert['cost'];
        }
    }

    return $cart_data;
}

function fn_twg_api_get_cart_products($cart_items, $lang_code = CART_LANGUAGE)
{
    if (empty($cart_items)) {
        return array();
    }

    $api_products = array();
    $image_params = Registry::get('addons.twigmo.cart_image_size');
    foreach ($cart_items as $k => $item) {
        $product = array (
            'product' => db_get_field(
                "SELECT product
                 FROM ?:product_descriptions
                 WHERE product_id = ?i AND lang_code = ?s",
                $item['product_id'],
                $lang_code
            ),
            'product_id' => $item['product_id'],
            'cart_id' => $k,
            'price' => $item['price'],
            'exclude_from_calculate' => !empty($item['extra']['exclude_from_calculate'])?
                $item['extra']['exclude_from_calculate'] : false,
            'amount' => $item['amount'],
            'company_id' => $item['company_id'],
            'company_name' => fn_get_company_name($item['company_id'])
        );
        $main_pair = !empty($item['main_pair'])? $item['main_pair']: fn_get_image_pairs(
            $item['product_id'],
            'product',
            'M',
            true,
            true,
            $lang_code
        );
        if (!empty($main_pair)) {
            $product['icon'] = Api::getAsApiObject(
                'images',
                TwigmoImage::getApiImageData(
                    $main_pair,
                    'product',
                    'icon',
                    $image_params
                )
            );
        }

        if (!empty($item['product_options'])) {
            $advanced_options = fn_get_selected_product_options_info($item['product_options']);
            $api_options = Api::getAsList(
                'cart_product_options',
                $advanced_options
            );

            if (!empty($api_options['option'])) {
                $product['product_options'] = $api_options['option'];
            }
        }

        $api_products[] = $product;
    }

    return $api_products;
}

function fn_twg_api_add_product_to_cart($products, &$cart)
{
    $products_data = array();

    foreach ($products as $product) {

        $cid = fn_generate_cart_id($product['product_id'], $product);

        if (!empty($products_data[$cid])) {
            $products_data[$cid]['amount'] += $product['amount'];
        }

        // Get product options images
        $product['combination_hash'] = $cid;
        if (!empty($product['combination_hash']) && !empty($product['product_options'])) {
            $image = fn_get_image_pairs($product['combination_hash'], 'product_option', 'M', true, true, CART_LANGUAGE);
            if (!empty($image)) {
                $product['main_pair'] = $image;
            }
        }

        $products_data[$cid] = $product;
     }

    $auth = & $_SESSION['auth'];

    // actions copied from the checkout.php 'add' action
    $ids = fn_add_product_to_cart($products_data, $cart, $auth);

    fn_save_cart_content($cart, $auth['user_id']);
    $cart['change_cart_products'] = true;
    fn_calculate_cart_content($cart, $auth, 'S', true, 'F', true);

    return $ids;
}

function fn_twg_get_random_ids($qty, $field, $table, $condition = '')
{
    // max quantity of rows in tables to use the mysql rand()
    // to prevent server load for large tables
    $max_rand_items = 1000;

    if (!empty($condition)) {
        $condition = 'WHERE ' . $condition;
    }

    $total = db_get_field("SELECT COUNT(*) as total FROM $table $condition");

    if ($total <= $qty) {
        return db_get_fields("SELECT $field FROM $table $condition");
    }

    if ($total < $max_rand_items) {
        return db_get_fields("SELECT $field FROM $table $condition ORDER BY RAND() LIMIT $qty");
    }

    $ids = array();
    $rands = array();
    $min_rand = 0;
    $max_rand = (int) $total - 1;

    for ($i = 0; $i < $qty; $i++) {
        $rand_num = rand($min_rand, $max_rand);

        while (in_array($rand_num, $rands)) {
            $rand_num++;
            if ($rand_num > $max_rand) {
                $rand_num = $min_rand;
            }
            echo $rand_num . ' <br/> ';
        }

        $rands[] = $rand_num;
        $ids[] = db_get_field("SELECT $field FROM $table $condition LIMIT $rand_num, 1");
    }

    return $ids;
}

/*
 * Get all product id from category (with/not subcategories)
 */
function fn_twg_get_category_product_ids($category_id, $get_sub  = false)
{
    if (empty($category_id)) {
        return false;
    }

    $_categories[] = $category_id;

    if ($get_sub) {

        $category_params = array (
            'id' => !empty($category_id) ? $category_id : 0,
            'type' => 'plain_tree'
        );

        $categories = fn_twg_api_get_categories($category_params);

        if (!empty($categories)) {
            foreach ($categories['category'] as $category) {
                $_categories[] = $category['category_id'];
            }
        }
    }

    $ids = !empty($_categories)?
        db_get_fields(
            "SELECT l.product_id
             FROM ?:products_categories AS l
             LEFT JOIN ?:products AS p
             ON p.product_id = l.product_id
             WHERE l.category_id IN(?a) AND l.link_type = 'M' AND p.status = 'A'
             ORDER BY l.position",
            $_categories
        ):
        array();

    return $ids;
}

/*
 * API functions adding data to response
 * @param Twigmo\ApiData $response
 * @param array $params array('items_per_page' => subj, 'page' => requested_page)
 * @param boolean $set_empty
 * @return boolean always true
 */
function fn_twg_set_response_pagination(&$response, $params, $set_empty = false)
{
    $params = array(
        'total_items' => !empty($params['total_items'])? $params['total_items']: count($response->getData()),
        'items_per_page' => $params['items_per_page'],
        'page' => $params['page']
    );
    $pagination = fn_generate_pagination($params);
    if (!empty($pagination)) {
        $response->setMeta($pagination['total_pages'], 'total_pages');
        $response->setMeta($pagination['total_items'], 'total_items');
        $response->setMeta($pagination['items_per_page'], 'items_per_page');
        $response->setMeta($pagination['current_page'], 'current_page');
    } elseif ($set_empty) {
        $response->setMeta(0, 'total_pages');
        $response->setMeta(0, 'total_items');
        $response->setMeta(0, 'items_per_page');
        $response->setMeta(1, 'current_page');
    }

    return true;
}

function fn_twg_get_category_descr($cid, $lang_code = CART_LANGUAGE)
{
    return db_get_field(
        'SELECT description FROM ?:category_descriptions WHERE category_id=?i AND lang_code=?s',
        $cid,
        $lang_code
    );
}

function fn_twg_set_response_products(
    &$response,
    $params,
    $items_per_page = 0,
    $lang_code = CART_LANGUAGE
) {
    list($products, $request) = fn_twg_api_get_products($_REQUEST, $items_per_page, $lang_code);
    if (!empty($products)) {
        $response->setResponseList($products);
        if (!empty($_REQUEST['cid'])) {
            $response->setMeta($_REQUEST['cid'], 'category_id');
            $category_descr = fn_twg_get_category_descr($_REQUEST['cid']);
            $category = !empty($category_descr) ?
                $category_descr :
                '';
            $response->setMeta($category, 'category');
        }
    }
    $pagination_params = array(
        'items_per_page' => !empty($params['items_per_page'])?
            $params['items_per_page']:
            TWG_RESPONSE_ITEMS_LIMIT,
        'page' => !empty($params['page'])?
            $params['page']:
            1,
        'total_items' =>  $request['total_items']
    );
    fn_twg_set_response_pagination($response, $pagination_params);
}

function fn_twg_set_response_categories(
    &$response,
    $params,
    $items_per_page = 0,
    $lang_code = CART_LANGUAGE
) {
    if (empty($items_per_page)) {
        $result = fn_twg_api_get_categories($params, $lang_code);
        $response->setMeta(
            db_get_field("SELECT COUNT(*) FROM ?:categories"),
            'total_items'
        );
        $response->setResponseList($result);

    } else {
        $default_params = array (
            'depth' => 0,
            'page' => 1
        );

        $params = array_merge($default_params, $params);
        $params['type'] = 'plain_tree';

        $categories = fn_twg_api_get_categories($params, $lang_code);

        if (!empty($categories)) {
            $total = count($categories['category']);
            $params['page'] = !empty($params['page']) ? $params['page'] : 1;
            fn_paginate($params['page'], $total, $items_per_page);

            $pagination = Registry::get('view')->getTemplateVars('pagination');

            $start = $pagination['prev_page'] * $pagination['items_per_page'];
            $end = $start + $items_per_page;
            $result = array();

            for ($i = $start; $i < $end; $i++) {
                if (!isset($categories['category'][$i])) {
                    break;
                }

                $result[] = $categories['category'][$i];
            }

            $response->setResponseList(array('category' => $result));
            $pagination_params = array(
                'items_per_page' => !empty($items_per_page)? $items_per_page : TWG_RESPONSE_ITEMS_LIMIT,
                'page' => !empty($_REQUEST['page'])? $_REQUEST['page'] : 1
            );
            fn_twg_set_response_pagination($response, $pagination_params);
        }

    }

    $category_id =  !empty($params['id']) ? $params['id'] : 0;

    if (!empty($category_id)) {
        $parent_data = db_get_row(
            "SELECT a.parent_id, b.category
             FROM ?:categories AS a
             LEFT JOIN ?:category_descriptions AS b
             ON a.parent_id = b.category_id
             WHERE a.category_id = ?i AND b.lang_code = ?s",
            $category_id,
            $lang_code
        );

        if (!empty($parent_data)) {
            $response->setMeta($parent_data['parent_id'], 'grand_id');
            $response->setMeta($parent_data['category'], 'grand_category');
        }

        $response->setMeta($category_id, 'category_id');
        $category_data = array_pop(
            db_get_array(
                "SELECT category, description
                 FROM ?:category_descriptions
                 WHERE category_id = ?i AND lang_code = ?s",
                $params['category_id'],
                $lang_code)
        );
        $response->setMeta($category_data['category'], 'category_name');
        $response->setMeta($category_data['description'], 'description');
    }

}

function fn_twg_set_response_catalog(
    &$response,
    $params,
    $items_per_page = 0,
    $lang_code = CART_LANGUAGE
) {
    // supported params:
    // id - category id
    // sort_by - products sort
    // sort_order - products sort order
    // page - products page number
    // items_per_page
    $params['category_id'] = !empty($params['category_id']) ? $params['category_id'] : 0;
    $params['page'] = empty($params['page']) ? 1 : $params['page'];

    $response->setData($params['category_id'], 'category_id');
    if (!empty($params['category_id'])) {
        $category_data = array_pop(
            db_get_array(
                "SELECT category, description "
                 . "FROM ?:category_descriptions "
                 . "WHERE category_id = ?i AND lang_code = ?s",
                $params['category_id'],
                $lang_code
            )
        );
        $response->setData($category_data['category'], 'category_name');
        $response->setData($category_data['description'], 'description');
    }

    if (empty($params['page']) || $params['page'] == 1) {
        $category_params = array (
            'id' => !empty($params['category_id']) ? $params['category_id'] : 0,
            'type' => 'one_level'
        );

        $categories = fn_twg_api_get_categories($category_params, $lang_code);

        if (!empty($categories['category'])) {
            $response->setData($categories['category'], 'subcategories');
        }
    }

    if (!empty($params['category_id'])) {
        // set products
        $params['cid'] = $params['category_id'];
        list($products, $params) = fn_twg_api_get_products($params, $items_per_page, $lang_code);

        if (!empty($products['product'])) {
            $response->setData($products['product'], 'products');
        }
    }
    $pagination_params = array(
        'items_per_page' => !empty($items_per_page)? $items_per_page : TWG_RESPONSE_ITEMS_LIMIT,
        'page' => !empty($params['page'])? $params['page'] : 1,
        'total_items' => !empty($params['total_items'])? $params['total_items']: null
    );
    fn_twg_set_response_pagination($response, $pagination_params, true);
}

function fn_twg_api_get_base_statuses($add_hidden = true, $lang_code = CART_LANGUAGE)
{
    $base_statuses = array (
        array('A', __('active', $lang_code), '#97CF4D'),
        array('D', __('disabled', $lang_code), '#D2D2D2'),
    );

    if ($add_hidden) {
        $base_statuses[] = array('H', __('hidden', $lang_code), '#8D8D8D');
    }

    $api_statuses = array();
    foreach ($base_statuses as $status) {
        $api_statuses[] = array (
            'code' => $status[0],
            'description' => $status[1],
            'color' => $status[2]
        );
    }

    return $api_statuses;
}

function fn_twg_api_get_object($response, $object_type, $params)
{
    $pattern = fn_get_schema('api', $object_type, 'php', false);
    $condition = array();

    if (!empty($pattern['key'])) {
        $api_key_id = current($pattern['key']);
        if ($pattern['fields'][$api_key_id]['db_field']) {
            $key_id = $pattern['fields'][$api_key_id]['db_field'];
            $condition = array($key_id => $params['id']);
        }
    }

    if (empty($condition)) {
        $response->addError(
            'ERROR_WRONG_OBJECT_DATA',
            str_replace(
                '[object]',
                $object_type,
                __(
                    'twgadmin_wrong_api_object_data'
                )
            )
        );
        $response->returnResponse();
    }

    $objects = Api::getApiSchemaData($object_type, $condition);

    if (empty($objects)) {
        $response->addError(
            'ERROR_OBJECT_WAS_NOT_FOUND',
            str_replace(
                '[object]',
                $object_type,
                __(
                    'twgadmin_object_was_not_found'
                )
            )
        );
        $response->returnResponse();
    }

    $api_data = current($objects[$pattern['object_name']]);
    $response->setData($api_data);
    $response->returnResponse($pattern['object_name']);
}

function fn_twg_get_payment_options($payment_id)
{
    $_template =  db_get_field(
        "SELECT template FROM ?:payments WHERE payment_id = ?i",
        $payment_id
    );
    $template = basename($_template);
    if ($template && preg_match('/(.+)\.tpl/', $template, $matches)) {
        $schema = fn_get_schema('api/payments', $matches[1]);
        // Change date fields name
        if (is_array($schema)) {
            foreach ($schema as $key => $option) {
                if ($option['name'] == 'start_date') {
                    $schema[$key]['name'] = 'start';
                }
                if ($option['name'] == 'expiry_date') {
                    $schema[$key]['name'] = 'expiry';
                }
            }
        }

        return $schema;
    }

    return false;
}

function fn_twg_api_process_user_data($user, $response, $lang_code = CART_LANGUAGE)
{
    $user = fn_twg_parse_api_object($user, 'users');

    $_auth = & $_SESSION['auth'];

    if (!empty($user['user_id']) && $user['user_id'] != $_auth['user_id']) {
        $response->addError(
            'ERROR_ACCESS_DENIED',
            __('access_denied', $lang_code)
        );
        $response->returnResponse();
    }

    if (empty($user['user_id'])) {
        $user['user_id'] = !empty($_auth['user_id']) ? $_auth['user_id'] : 0;
    }

    if (empty($user['user_id']) && !empty($user['password_hash'])) {
        $user['password1'] = 'tmp';
        $user['password2'] = 'tmp';
    }

    $result = fn_twg_api_update_user($user, $_auth);

    if (!$result) {
        if (!fn_twg_set_internal_errors($response, 'ERROR_FAIL_CREATE_USER')) {
            $response->addError(
                'ERROR_FAIL_CREATE_USER',
                __('fail_create_user', $lang_code)
            );
        }
        $response->returnResponse();
    }

    $_SESSION['cart']['user_data'] = fn_get_user_info($_auth['user_id']);

    if (!empty($user['password_hash'])) {
        db_query(
            "UPDATE ?:users SET password = ?s WHERE user_id = ?i",
            $user['password_hash'],
            $_auth['user_id']
        );
    }
}

function fn_twg_check_api_user_data(
    $user,
    $location = 'C',
    $lang_code = CART_LANGUAGE
) {
    if (empty($user['user_id']) && empty($user['is_complete_data'])) {
        return false;
    }

    if (!empty($user['profiles'])) {
        $user = array_merge($user, current($user['profiles']));
        unset($user['profiles']);
    }

    $profile_fields = fn_get_profile_fields($location);

    $is_complete_fields =  true;

    foreach ($profile_fields as $section_fields) {
        foreach ($section_fields as $field) {
            if ($field['required'] == 'Y' && empty($user[$field['field_name']])) {
                $is_complete_fields =  false;
                fn_set_notification(
                    'E',
                    __('error'),
                    str_replace(
                        '[field]',
                        $field['description'],
                        __(
                            'error_twg_validator_required',
                            $lang_code
                        )
                    )
                );
            }
        }
    }

    if (!$is_complete_fields) {
        return false;
    }

    return $user;
}

function fn_twg_api_update_user($user, &$auth, $notify_user = false)
{
    if (!$user = fn_twg_check_api_user_data($user)) {
        return false;
    }

    if (!empty($user['user_id'])) {
        $user_data = db_get_row(
            "SELECT * FROM ?:users WHERE user_id = ?i",
            $user['user_id']
        );
        $user_data = array_merge($user_data, $user);
    } else {
        $user['user_id'] = 0;
        $user_data = $user;
    }

    $user_data['password1'] =
        !empty($user_data['password1']) ?
            $user_data['password1'] :
            '';
    $result = fn_update_user(
        $user['user_id'],
        $user_data,
        $auth,
        true,
        $notify_user
    );

    return $result;
}

function fn_twg_set_internal_errors(&$response, $error_code)
{
    $notifications = fn_get_notifications();

    if (empty($notifications)) {
        return false;
    }

    $err_counter = 1;
    foreach ($notifications as $n) {
        if ($n['type'] != 'N') {
            $response->addError($error_code . $err_counter, $n['message']);
            $err_counter++;
        }
    }

    if ($err_counter > 1) {
        return true;
    }

    return false;
}

function fn_twg_api_set_cart_user_data($user_data, $response, $lang_code = CART_LANGUAGE) {
    $cart = & $_SESSION['cart'];

    // User update or registration
    $_user = fn_twg_parse_api_object($user_data, 'users');
    $user = fn_twg_check_api_user_data($_user, 'C', $lang_code);
    if (empty($user)) {
        if (!fn_twg_set_internal_errors($response, 'ERROR_FAIL_UPDATE_USER')) {
            $error_text = str_replace('[object]', 'user', __('wrong_api_object_data', $lang_code));
            $response->addError('ERROR_WRONG_OBJECT_DATA', $error_text);
        }
        $response->returnResponse();
    }
    $cart['user_data'] = $user;

    return true;
}


function fn_twg_api_get_product_groups(&$params)
{
    list (, $product_groups) = fn_calculate_cart_content($params['cart'], $params['auth']);

    $product_groups = $product_groups ? $product_groups : array();
    foreach($product_groups as $group_key => $product_group) {
        $product_groups[$group_key]['group_key'] = $group_key;
    }
    return $product_groups;
}

/**
 * Add vendors list to products search result
 * @param array $response
 * @param array $params
 * @param string $lang_code
 */
function fn_twg_add_response_vendors(&$response, $params)
{
    if (empty($params['q'])) {
        return;
    }

    $params['q'] = trim(preg_replace('/\s+/', ' ', $params['q']));

    $all_comapnies = fn_get_companies(array('status' => 'A'), $_SESSION['auth']);

    $all_comapnies = $all_comapnies[0];
    if (empty($all_comapnies)) {
        return;
    }

    $companies = array();
    foreach ($all_comapnies as $company) {
        if (preg_match('/\b' . preg_quote($company['company'], '/') . '\b/iU', $params['q'])) {
            $logo = unserialize($company['logos']);
            if (empty($logo['Customer_logo'])) {
                $url = '';
            } else {
                $url =
                    (defined('HTTPS') ?
                        'https://' . Registry::get('config.http_host') :
                        'http://' . Registry::get('config.https_host')
                    )
                    . Registry::get('config.images_path')
                    . $logo['Customer_logo']['filename'];
            }
            $companies[] = array(
                'company_id' => $company['company_id'],
                'title' => $company['company'],
                'q' => trim(
                    preg_replace(
                        '/\b' . $company['company'] . '\b/iU',
                        '',
                        $params['q']
                    )
                ),
                'icon' => array('url' => $url)
            );
        }
    }
    $response->setMeta($companies, 'companies');
    if (empty($response->data) and $companies) {
        $response->setMeta(1, 'current_page');
    }
}

/**
 * Returns info for homepage
 * @param object $response
 */
function fn_twg_set_response_homepage(&$response)
{
    $home_page_content = Registry::get('addons.twigmo.home_page_content');

    if (empty($home_page_content)) {
        $home_page_content = 'random_products';
    }

    if (
        $home_page_content == 'home_page_blocks'
        or $home_page_content == 'tw_home_page_blocks'
    ) {
        // Use block manager: get blocks

        if ($home_page_content == 'home_page_blocks') {
            $location = 'index.index';
        } else {
            $location = 'twigmo.post';
        }
        $blocks = TwigmoBlock::getBlocksForLocation(
            $location,
            Registry::get('addons.twigmo.block_types')
        );
        // Return blocks
        $response->setData($blocks);
    } else {
        $block = array();
        // Random products or category products
        if ($home_page_content == 'random_products') {
            $product_ids = fn_twg_get_random_ids(
                TWG_RESPONSE_ITEMS_LIMIT,
                'product_id',
                '?:products',
                db_quote("status = ?s", 'A')
            );
            $block['title'] = 'random_products';
        } else {
            $product_ids =
                fn_twg_get_category_product_ids($home_page_content, false) or array();
            $block['title'] = fn_get_category_name($home_page_content);
        }
        $block['products'] = fn_twg_api_get_products(
            array('pid' => $product_ids),
            count($product_ids)
        );
        $block['total_items'] = count($block['products']);
        $response->setData(array($block));
    }
}

/**
 * Get locations info use default layout's locations
 * @return array
 */
function fn_twg_get_locations_info()
{
    $locations_info = array();
    $locations = array(
        'twigmo' => 'twigmo.post',
        'index' => 'index.index'
    );
    $defaultLayoutId = fn_twg_get_default_layout_id();
    foreach ($locations as $loc => $dispatch) {
        $location = Location::instance($defaultLayoutId)->get($dispatch);
        if (empty($location['location_id'])){
            continue;
        }
        $locations_info[$loc] = $location['location_id'];
    }
    return $locations_info;
}

/**
 * @return integer
 */
function fn_twg_get_default_layout_id()
{
    $condition = "";
    if (fn_allowed_for('ULTIMATE')) {
        $company_id = Registry::get('runtime.company_id');
        $condition = fn_get_company_condition(
            '?:bm_layouts.company_id',
            true,
            $company_id
        );
    }
    $condition .= db_quote(" AND is_default = 1");
    $layout = db_get_row("SELECT * FROM ?:bm_layouts WHERE 1 ?p", $condition);
    return (int) $layout['layout_id'];
}

/**
 * Prepare profile fields - delete unnecessary fields and also make arrays
 * instead of objects to have an ability use foreach in closure templates
 */
function fn_twg_prepare_profile_fields($fields, $only_reguired)
{
    $allowed_keys = array('C', 'B', 'S');

    foreach (array_keys($fields) as $fieldkey) {
        if (!in_array($fieldkey, $allowed_keys)) {
            unset($fields[$fieldkey]);
            continue;
        }

        foreach ($fields[$fieldkey] as $field_key => $field) {
            if ($only_reguired == 'Y' and $field['required'] == 'N') {
                unset($fields[$fieldkey][$field_key]);
                continue;
            }
            if (!empty($field['values'])) {
                $values = array();
                foreach ($field['values'] as $value_id => $option_value) {
                    $values[] = array('id' => $value_id, 'value' => $option_value);
                }
                $fields[$fieldkey][$field_key]['values'] = $values;
            }
        }
        $fields[$fieldkey] = array_values($fields[$fieldkey]);
        if (empty($fields[$fieldkey])) {
            unset($fields[$fieldkey]);
            continue;
        }
    }

    return $fields;
}

/**
 * If stat addon is activated - we should keep current url and description
 */
function fn_twg_get_banner_url($banner_id, $url)
{
    if (Registry::get('addons.statistics.status') == 'A') {
        $url = db_get_field('SELECT url FROM ?:banners WHERE banner_id=?i', $banner_id);
    }

    return $url;
}

/**
 * We should form twigmo links for internal pages
 * @param string $url
 * @param string $target
 * @param string $type
 * @param int $banner_id
 */
function fn_twg_get_banner_onclick($url, $target, $type, $banner_id)
{
    $onclick = '';
    if ($target == 'T' and !empty($url) and $type == 'G') {
        if (Registry::get('addons.statistics.status') == 'A') {
            $url = db_get_field('SELECT url FROM ?:banners WHERE banner_id=?i', $banner_id);
        }
        // Process SEO links
        if (Registry::get('addons.seo.status') == 'A') {
            $_SERVER['REQUEST_URI'] = $url;
            $request = array('sef_rewrite' => 1);
            $result = '';
            $area = AREA;
            $is_allowed_url = true;
            fn_seo_get_route($request, $result, $area, $is_allowed_url);
            if ($_SERVER['REQUEST_URI'] != $url) {
                $url = $_SERVER['REQUEST_URI'];
            }
        }
        // Only for internal links
        $twigmo_links =
            array(
                array(
                    'pattern' => '/product_id=([0-9]+)/',
                    'onclick' => 'addToStack(product, {id: [key]});'
                ),
                array(
                    'pattern' => '/page_id=([0-9]+)/',
                    'onclick' => 'addToStack(cmsPage, {page_id: [key]});'
                ),
                array(
                    'pattern' => '/category_id=([0-9]+)/',
                    'onclick' => 'addToStack(catalog, {category_id: [key]});'
                ),
            );
        $matches = array();
        foreach ($twigmo_links as $link) {
            if (preg_match($link['pattern'], $url, $matches)) {
                $onclick = str_replace('[key]', $matches[1], $link['onclick']);
                break;
            }
        }
    }

    return $onclick;
}

/**
 * Get user info
 */
function fn_twg_get_user_info($params)
{
    $profile = array();
    if (!$params['user_id']) {
        $profile['user_id'] = 0;
    } else {
        $profile = fn_get_user_info($params['user_id']);
    }
    if ($params['mode'] == 'checkout') {
        $profile = array_merge($profile, $_SESSION['cart']['user_data']);
    }
    // Clear empty profile fields
    if (!empty($profile['fields'])) {
        $profile['fields'] = array_filter($profile['fields']);
    }
    $profile['ship_to_another']['profile'] =
        fn_check_shipping_billing(
            $profile,
            fn_get_profile_fields()
        );
    $checkout_pfields = fn_get_profile_fields('O');
    $profile['ship_to_another']['cart'] =
                                            (

                                                fn_check_shipping_billing(
                                                    $profile,
                                                    $checkout_pfields
                                                )

                                                ||

                                                !fn_compare_shipping_billing(
                                                    $checkout_pfields
                                                )

                                            );
    if ($params['user_id']) {
        $profile['b_email'] =
            !empty($profile['b_email']) ?
                $profile['b_email'] :
                $profile['email'];
        $profile['s_email'] =
            !empty($profile['s_email']) ?
                $profile['s_email'] :
                $profile['email'];
    }

    return $profile;
}

/**
 * Get user info
 * @param int $page_id
 */
function fn_twg_api_get_page($page_id)
{
    if (!$page_id) {
        return false;
    }
    $page = fn_get_page_data($page_id);
    if (!$page) {
        return false;
    }

    return Api::getAsApiObject('page', $page);
}

/**
 * Get form elements
 * @param array $elements
 */
function fn_twg_api_get_form_elements($elements)
{
    $result = array();
    if ($elements) {
        foreach ($elements as $element) {
            $element = Api::getAsApiObject('form_element', $element);
            if (empty($element['variants'])) {
                unset($element['variants']);
            }
            $result[] = $element;
        }
    }

    return $result;
}

/**
 * Get form info
 * @param array $page_id
 */
function fn_twg_api_get_form_info($form)
{
    if (!$form) {
        return false;
    }

    return array(
        'sent_message' => $form['general']['L'],
        'elements' => fn_twg_api_get_form_elements($form['elements'])
    );
}

/**
 * Check if grid belongs to twigmo location
 * @param int $grid_id
 */
function fn_twg_is_twigmo_grid($grid_id)
{
    $grid = Grid::getById($grid_id);
    if (!$grid) {
        return false;
    }
    $container = Container::getById($grid['container_id']);

    return fn_twg_is_twigmo_location($container['location_id']);
}

/**
 * Check if it's a twigmo location
 * @param int $grid_id
 */
function fn_twg_is_twigmo_location($location_id)
{
    // Compare with twigmo location
    $twigmo_location = Location::instance()->get('twigmo.post');
    if (empty($twigmo_location) || !empty($twigmo_location['is_default']) || (!empty($twigmo_location['location_id']) && $twigmo_location['location_id'] != $location_id)) {
        return false;
    }

    return true;
}

/**
 * Get external info url
 * @param string $url
 * @return string
 */
function fn_twg_get_external_info_url($url)
{
    $url = trim($url);
    if (!$url) {
        return '';
    }

    return (strpos($url, 'http') === 0 ? '' : 'http://') . $url;
}

/**
 * Get available product sortings
 * @return array - [sort_label, sort_order, sort_by]
 */
function fn_twg_get_sortings()
{
    $sortings = fn_get_products_sorting(false);
    $sorting_orders = fn_get_products_sorting_orders();
    $avail_sorting = Registry::get('settings.Appearance.available_product_list_sortings');
    $default_sorting = fn_get_default_products_sorting();

    $result = array($default_sorting);
    $result[0]['sort_label'] = __(
        'sort_by_'
        . $default_sorting['sort_by']
        . '_'
        . $default_sorting['sort_order']
    );

    // Reverse sorting (for usage in view)
    $default_sorting['sort_order'] =
        $default_sorting['sort_order'] == 'asc' ?
            'desc' :
            'asc';
    foreach ($sortings as $option => $value) {
        if ($default_sorting['sort_by'] == $option) {
            $sort_order = $default_sorting['sort_order'];
        } else {
            if ($value['default_order']) {
                $sort_order = $value['default_order'];
            } else {
                $sort_order = 'asc';
            }
        }
        foreach ($sorting_orders as $sort_order) {
            if (
                $default_sorting['sort_by'] != $option
                or $default_sorting['sort_order'] == $sort_order
) {
                if (
                    !$avail_sorting
                    or !empty($avail_sorting[$option . '-' . $sort_order])
                    and $avail_sorting[$option . '-' . $sort_order] == 'Y'
                ) {
                    $result[] = array(
                        'sort_by' => $option,
                        'sort_order' => $sort_order,
                        'sort_label' => __(
                            'sort_by_' . $option . '_' . $sort_order
                        )
                    );
                }
            }
        }
    }

    return $result;
}

function fn_twg_get_feature_value(
    $value,
    $feature_type,
    $value_int,
    $variant_id,
    $variants
) {
    if ($feature_type == "D") {
        $value = fn_date_format(
            $value_int,
            Registry::get('settings.Appearance.date_format')
        );
    } elseif ($feature_type == "M") {
        $value = array();
        foreach ($variants as $variant) {
            if ($variant['selected']) {
                $value[] = $variant['variant'];
            }
        }
    } elseif ($value_int) {
        $value = $value_int;
    } elseif ($variant_id) {
        $value = $variants[$variant_id]['variant'];
    }

    return $value;
}

/**
 * Prepare all settings, wich should be passed to js
 */
function fn_twg_get_all_settings()
{
    $settings = array();

    $settings['currency'] = Registry::get('currencies.' . CART_SECONDARY_CURRENCY);
    $settings['primaryCurrency'] = Registry::get('currencies.' . CART_PRIMARY_CURRENCY);

    $addon_settings = Registry::get('addons.twigmo');

    $settings['logoURL'] =
        empty($addon_settings['logoURL'])?
            TwigmoImage::getDefaultLogoUrl():
            $addon_settings['logoURL'];
    if (defined('HTTPS')) {
        $settings['logoURL'] = str_replace(
            'http://',
            'https://',
            $settings['logoURL']
        );
    }
    $settings['selected_skin'] =
        empty($addon_settings['selected_skin']) ?
            'default' :
            $addon_settings['selected_skin'];
    $settings['url_for_facebook'] =
        isset($addon_settings['url_for_facebook']) ?
            fn_twg_get_external_info_url($addon_settings['url_for_facebook']) :
            '';
    $settings['url_for_twitter'] =
        isset($addon_settings['url_for_twitter']) ?
            fn_twg_get_external_info_url($addon_settings['url_for_twitter']) :
            '';
    $settings['httpLocation'] =
        'http://'
        . Registry::get('config.http_host')
        . Registry::get('config.http_path')
        . '/';
    $settings['httpsLocation'] =
        'https://'
        . Registry::get('config.https_host')
        . Registry::get('config.https_path')
        . '/';

    $settings['requestUrl'] = '';
    $settings['customerIndex'] = Registry::get('config.customer_index');
    $settings['request'] = $_REQUEST;

    $settings['cacheLifeTime'] = 0;

    $settings['theme'] = array(
        "header_theme" => "b",
        "footer_theme" => "a",
        "data_theme" => "c",
        "background_theme" => "b",
        "button_theme" => "c",
        "header_button_theme" => "a",
        "navigation_button_theme" => "a",
        "data_count_theme" => "e",
        "data_divider_theme" => "d"
    );

    $needed_langvars = Lang::getNeededLangvars();

    $settings['lang'] = Lang::getCustomerLangVars();

    foreach ($needed_langvars as $needed_langvar) {
        $settings['lang'][$needed_langvar] = __($needed_langvar);
    }

    // Countries/states
    list($countries,) = fn_get_countries(
        array(),
        0,
        CART_LANGUAGE
    );
    $settings = array_merge(
        $settings,
        Api::getAsList(
            'countries',
            $countries
        )
    );
    $states = fn_get_all_states();
    // Unset country_code field
    foreach ($states as $country_id => $country) {
        foreach (array_keys($country) as $state_id) {
            unset($states[$country_id][$state_id]['country_code']);
        }
    }
    $settings['states'] = $states;

    // Info pages
    $pages = TwigmoBlock::getBlocksForLocation('twigmo.post', array('pages'));
    $settings['info_pages'] = array();
    foreach ($pages as $page) {
        $settings['info_pages'] = array_merge(
            $settings['info_pages'],
            $page['pages']['page']
        );
    }
    // If page link begin with # then interpret this link as twigmo page
    foreach ($settings['info_pages'] as $k => $page) {
        if (preg_match('/^\#.*$/', $page['link'])) {
            $settings['info_pages'][$k]['twigmo_page'] = substr($page['link'], 1);
        }
    }

    // Only required profile fields
    $only_required =
        isset($addon_settings['only_req_profile_fields']) ?
            $addon_settings['only_req_profile_fields']  :
            'N';
    $settings['profileFields'] = fn_twg_prepare_profile_fields(
        fn_get_profile_fields(),
        $only_required
    );
    $settings['profileFieldsCheckout'] = fn_twg_prepare_profile_fields(
        fn_get_profile_fields('O'), $only_required
    );

    $settings['titles'] = array();//array_values(fn_twg_get_static_data_section('T'));

    $user_info_params = array(
        'user_id' => $_SESSION['auth']['user_id'],
        'mode' => Registry::get('runtime.mode')
    );
    $settings['profile'] = fn_twg_get_user_info($user_info_params);

    $settings['cart'] = fn_twg_api_get_session_cart($_SESSION['cart']);

    $settings['sortings'] = fn_twg_get_sortings();

    $settings['security_hash'] = fn_generate_security_hash();

    $controller =
        $addon_settings['home_page_content'] == 'home_page_blocks' ?
            'index.index' :
            'twigmo.post';
    $settings['home_page_title'] = fn_twg_get_location_page_title($controller);

    $settings['geolocation'] =
        isset($addon_settings['geolocation']) ?
            $addon_settings['geolocation'] :
            'Y';

    $settings['productType'] = PRODUCT_EDITION;

    $settings['vendorProfileFields'] = $settings['profileFields'];

    $settings['languages'] = array_values(Lang::getLanguages());

    $settings['cart_language'] = CART_LANGUAGE;

    $settings['cart_prices_w_taxes'] = Registry::get(
        'settings.Appearance.cart_prices_w_taxes'
    );
    $settings['companyName'] = Registry::get(
        'settings.Company.company_name'
    );
    $settings['no_image_path'] = Registry::get(
        'config.no_image_path'
    );

    // Suppliers
    $settings['suppliers_vendor'] = Registry::get(
        'settings.Suppliers.apply_for_vendor'
    );
    $settings['display_supplier'] = Registry::get(
        'settings.Suppliers.display_supplier'
    );

    // General section
    $fields = array(
        'use_email_as_login',
        'min_order_amount',
        'min_order_amount_type',
        'address_position',
        'agree_terms_conditions',
        'allow_negative_amount',
        'inventory_tracking',
        'allow_anonymous_shopping',
        'disable_anonymous_checkout',
        'tax_calculation'
    );
    foreach ($fields as $field) {
        $settings[$field] = Registry::get(
            'settings.General.' . $field
        );
    }

    $settings['default_location'] = array(
        'country' => Registry::get('settings.General.default_country'),
        'state' => Registry::get('settings.General.default_state')
    );

    $settings['show_modifiers'] = Registry::get(
        'settings.General.display_options_modifiers'
    );

    $settings['SEOEnabled'] = Registry::get('addons.seo.status') == 'A';

    if (fn_allowed_for('MULTIVENDOR')) {
        $settings['company_data'] = !empty($_SESSION['auth']['company_id'])? fn_get_company_data($_SESSION['auth']['company_id']): array();
    } else {
        $settings['company_data'] = array();
    }

    fn_set_hook('twg_get_all_settings', $settings);

    return $settings;
}

/**
 * Get blocks from central container
 * @param string $controller - controller.method
 * @return array ([block_id] => block_name)
 */
function fn_twg_get_location_page_title($controller = 'twigmo.post')
{
    $location = Location::instance()->get($controller);

    return $location['title'];
}

/**
 * Get text from skins.js config file
 * @param array $params 'skins_data_url', 'version' e.g. '2.3'
 * @return string
 */
function fn_twg_get_skins_data($params)
{
    $skins_data_url =
        (!empty($params['skins_data_url']) ? $params['skins_data_url'] : TWIGMO_skinS_CONFIG_URL)
        . $params['version']
        .'/skins.js';
    $text =  @file_get_contents ($skins_data_url);

    return $text;
}

/**
 * Parse skins config file text
 * @param string $text
 * @return array
 */
function fn_twg_parse_skins_data($text)
{
    if (empty($text)) {
        return array();
    }
    $css_filename_map = array('default' => 'basic');
    $css_file_prefix = 'custom_';
    $_text = explode("=", $text);
    $css_files = $__text = array();
    foreach ($_text as $row) {
        $__text[] = str_replace(array(' ', "\n", "\t", ";", "{", "}"), '', $row);
    }
    foreach ($__text as $index => $row) { // Get number of row, which contained skins data
        if (preg_match('/^default:/', $row)) {
            break;
        }
    }
    $__skins = explode(',', $__text[$index]);
    foreach ($__skins as $_skin) {
        $skin = explode(':', $_skin);
        if (in_array($skin[0], array_keys($css_filename_map))) {
           $skin_code = $css_filename_map[$skin[0]];
        } else {
           $skin_code = $skin[0];
        }
        $css_files[] = $css_file_prefix.$skin_code.'.css';
    }

    return $css_files;
}

/**
 * Shorthand for file_put_contents
 * @param array $params array('filename', 'variable', 'file_append_flag') Can be ommited
 * @return boolean always true
 */
function fpc($params)
{
    if (func_num_args() > 1) {
        $params = func_get_args();
    }
    if (empty($params[2])) {
        file_put_contents((string) $params[0], print_r($params[1], 1));
    } else {
        file_put_contents((string) $params[0], print_r($params[1], 1), FILE_APPEND);
    }

    return true;
}

/**
* Dump mysqli error into file 'mysql_errors'
* @param array $params array('query' => query string, 'error' => error string, 'backtrace' => backtrace)
* @return Bool always true
*/
function fn_twg_mysqli_error_dump($params)
{
    if (!empty($params['error'])) {
        file_put_contents(
            'mysql_errors',
            print_r(
                array(
                    $params['query'],
                    $params['error'],
                    $params['backtrace']
                ),
                1
            ),
            FILE_APPEND
        );
    }

    return true;
}

// ====== Func for schemas =================
function fn_twg_get_api_image_data($params)
{
    return TwigmoImage::getApiImageData($params);
}
// =========================================

// Check if it is product with the recurring plans
function fn_twg_check_for_recurring($recurring_plans)
{
   return (is_array($recurring_plans) and count($recurring_plans)) ? 'Y' : 'N';
}

// Check for addon upgrade wrapper (for addon.xml)
function fn_twg_check_for_upgrade()
{
    TwigmoUpgrade::checkForUpgrade();
}
