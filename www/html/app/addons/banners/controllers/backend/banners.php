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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD']	== 'POST') {

    fn_trusted_vars('banners', 'banner_data');
    $suffix = '';

    //
    // Delete banners
    //
    if ($mode == 'm_delete') {
        foreach ($_REQUEST['banner_ids'] as $v) {
            fn_delete_banner_by_id($v);
        }

        $suffix = '.manage';
    }

    //
    // Add/edit banners
    //
    if ($mode == 'update') {
        $banner_id = fn_update_banner($_REQUEST['banner_data'], $_REQUEST['banner_id'], DESCR_SL);

        $suffix = ".update?banner_id=$banner_id";
    }

    return array(CONTROLLER_STATUS_OK, "banners$suffix");
}

if ($mode == 'update') {
    $banner = fn_get_banner_data($_REQUEST['banner_id'], DESCR_SL);

    if (empty($banner)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    Registry::set('navigation.tabs', array (
        'general' => array (
            'title' => __('general'),
            'js' => true
        ),
    ));

    Registry::get('view')->assign('banner', $banner);

} elseif ($mode == 'manage' || $mode == 'picker') {

    list($banners, ) = fn_get_banners(array(), DESCR_SL);

    Registry::get('view')->assign('banners', $banners);

} elseif ($mode == 'delete') {
    if (!empty($_REQUEST['banner_id'])) {
        fn_delete_banner_by_id($_REQUEST['banner_id']);
    }

    return array(CONTROLLER_STATUS_REDIRECT, "banners.manage");
}

//
// Categories picker
//
if ($mode == 'picker') {
    Registry::get('view')->display('addons/banners/pickers/banners/picker_contents.tpl');
    exit;
}

function fn_update_banner($data, $banner_id, $lang_code = DESCR_SL)
{
    if (isset($data['timestamp'])) {
        $data['timestamp'] = fn_parse_date($data['timestamp']);
    }

    $data['localization'] = empty($data['localization']) ? '' : fn_implode_localizations($data['localization']);

    if (!empty($banner_id)) {
        db_query("UPDATE ?:banners SET ?u WHERE banner_id = ?i", $data, $banner_id);
        db_query("UPDATE ?:banner_descriptions SET ?u WHERE banner_id = ?i AND lang_code = ?s", $data, $banner_id, $lang_code);
    } else {
        $banner_id = $data['banner_id'] = db_query("REPLACE INTO ?:banners ?e", $data);

        foreach (fn_get_translation_languages() as $data['lang_code'] => $v) {
            db_query("REPLACE INTO ?:banner_descriptions ?e", $data);
        }
    }

    fn_attach_image_pairs('banners_main', 'promo', $banner_id, $lang_code);

    return $banner_id;
}
