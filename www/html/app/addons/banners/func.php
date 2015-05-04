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

use Tygh\Registry;
use Tygh\BlockManager\Block;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Get banners
//
function fn_get_banners($params, $lang_code = CART_LANGUAGE)
{
    $default_params = array(
        'items_per_page' => 0,
    );

    $params = array_merge($default_params, $params);

    $sortings = array(
        'position' => '?:banners.position',
        'timestamp' => '?:banners.timestamp',
        'name' => '?:banner_descriptions.banner',
    );

    $condition = $limit = '';

    if (!empty($params['limit'])) {
        $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
    }

    $sorting = db_sort($params, $sortings, 'name', 'asc');

    $condition = (AREA == 'A') ? '' : " AND ?:banners.status = 'A' ";
    $condition .= fn_get_localizations_condition('?:banners.localization');

    if (!empty($params['item_ids'])) {
        $condition .= db_quote(' AND ?:banners.banner_id IN (?n)', explode(',', $params['item_ids']));
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(" AND (?:banners.timestamp >= ?i AND ?:banners.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    fn_set_hook('get_banners', $params, $condition, $sorting, $limit, $lang_code);

    $fields = array (
        '?:banners.banner_id',
        '?:banners.type',
        '?:banners.target',
        '?:banners.status',
        '?:banners.url',
        '?:banners.position',
        '?:banner_descriptions.banner',
        '?:banner_descriptions.description'
    );

    if (fn_allowed_for('ULTIMATE')) {
        $fields[] = '?:banners.company_id';
    }

    $banners = db_get_array(
        "SELECT ?p FROM ?:banners " .
        "LEFT JOIN ?:banner_descriptions ON ?:banner_descriptions.banner_id = ?:banners.banner_id AND ?:banner_descriptions.lang_code = ?s" .
        "WHERE 1 ?p ?p ?p",
        implode(", ", $fields), $lang_code, $condition, $sorting, $limit
    );

    foreach ($banners as $k => $v) {
        $banners[$k]['main_pair'] = fn_get_image_pairs($v['banner_id'], 'promo', 'M', true, false, $lang_code);
    }

    fn_set_hook('get_banners_post', $banners, $params);

    return array($banners, $params);
}

//
// Get specific banner data
//
function fn_get_banner_data($banner_id, $lang_code = CART_LANGUAGE)
{
    $status_condition = (AREA == 'A') ? '' : " AND ?:banners.status IN ('A', 'H') ";

    $fields = array (
        '?:banners.banner_id',
        '?:banners.status',
        '?:banners.url',
        '?:banner_descriptions.banner',
        '?:banners.type',
        '?:banners.target',
        '?:banners.localization',
        '?:banners.timestamp',
        '?:banners.position',
        '?:banner_descriptions.description'
    );

    if (fn_allowed_for('ULTIMATE')) {
        $fields[] = '?:banners.company_id as company_id';
    }

    $banner = db_get_row(
        "SELECT ?p FROM ?:banners " .
        "LEFT JOIN ?:banner_descriptions ON ?:banner_descriptions.banner_id = ?:banners.banner_id AND ?:banner_descriptions.lang_code = ?s " .
        "WHERE ?:banners.banner_id = ?i ?p",
        implode(", ", $fields), $lang_code, $banner_id, $status_condition
    );

    if (!empty($banner)) {
        $banner['main_pair'] = fn_get_image_pairs($banner['banner_id'], 'promo', 'M', true, false, $lang_code);
    }

    return $banner;
}

/**
 * Hook for deleting store banners
 *
 * @param int $company_id Company id
 */
function fn_banners_delete_company(&$company_id)
{
    if (fn_allowed_for('ULTIMATE')) {
        $bannser_ids = db_get_fields("SELECT banner_id FROM ?:banners WHERE company_id = ?i", $company_id);

        foreach ($bannser_ids as $banner_id) {
            fn_delete_banner_by_id($banner_id);
        }
    }
}

/**
 * Deletes banner and all related data
 *
 * @param int $banner_id Banner identificator
 */
function fn_delete_banner_by_id($banner_id)
{
    if (!empty($banner_id) && fn_check_company_id('banners', 'banner_id', $banner_id)) {
        db_query("DELETE FROM ?:banners WHERE banner_id = ?i", $banner_id);
        db_query("DELETE FROM ?:banner_descriptions WHERE banner_id = ?i", $banner_id);

        fn_set_hook('delete_banners', $banner_id);

        Block::instance()->removeDynamicObjectData('banners', $banner_id);

        fn_delete_image_pairs($banner_id, 'promo');
    }
}

//
// Get banner name
//
function fn_get_banner_name($banner_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($banner_id)) {
        return db_get_field("SELECT banner FROM ?:banner_descriptions WHERE banner_id = ?i AND lang_code = ?s", $banner_id, $lang_code);
    }

    return false;
}

if (!fn_allowed_for('ULTIMATE:FREE')) {
    function fn_banners_localization_objects(&$_tables)
    {
        $_tables[] = 'banners';
    }
}

if (fn_allowed_for('ULTIMATE')) {
    function fn_banners_ult_check_store_permission($params, &$object_type, &$object_name, &$table, &$key, &$key_id)
    {
        if (Registry::get('runtime.controller') == 'banners' && !empty($params['banner_id'])) {
            $key = 'banner_id';
            $key_id = $params[$key];
            $table = 'banners';
            $object_name = fn_get_banner_name($key_id, DESCR_SL);
            $object_type = __('banner');
        }
    }
}
