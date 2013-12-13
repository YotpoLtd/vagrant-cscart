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
use Tygh\Settings;
use Tygh\Addons\SchemesManager;
use Tygh\Registry;
use Tygh\Navigation\LastView;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    fn_trusted_vars (
        'addon_data'
    );

    if ($mode == 'update') {
        if (isset($_REQUEST['addon_data'])) {
            fn_update_addon($_REQUEST['addon_data']);
        }
    }

    return array(CONTROLLER_STATUS_OK, "addons.manage");
}

if ($mode == 'update') {
    $addon_name = addslashes($_REQUEST['addon']);

    $section = Settings::instance()->getSectionByName($_REQUEST['addon'], Settings::ADDON_SECTION);

    if (empty($section)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $subsections = Settings::instance()->getSectionTabs($section['section_id'], CART_LANGUAGE);
    $options = Settings::instance()->getList($section['section_id']);

    fn_update_lang_objects('sections', $subsections);
    fn_update_lang_objects('options', $options);

    Registry::get('view')->assign('options', $options);
    Registry::get('view')->assign('subsections', $subsections);

    $addon =  db_get_row(
        'SELECT a.addon, a.status, b.name as name, b.description as description, a.separate '
        . 'FROM ?:addons as a LEFT JOIN ?:addon_descriptions as b ON b.addon = a.addon AND b.lang_code = ?s WHERE a.addon = ?s'
        . 'ORDER BY b.name ASC',
        CART_LANGUAGE, $_REQUEST['addon']
    );

    if ($addon['separate'] == true || !defined('AJAX_REQUEST')) {
        Registry::get('view')->assign('separate', true);
        Registry::get('view')->assign('addon_name', $addon['name']);
    }

} elseif ($mode == 'install') {

    fn_install_addon($_REQUEST['addon']);

    return array(CONTROLLER_STATUS_OK, "addons.manage");

} elseif ($mode == 'uninstall') {

    fn_uninstall_addon($_REQUEST['addon']);

    return array(CONTROLLER_STATUS_OK, "addons.manage");

} elseif ($mode == 'update_status') {

    if (($res = fn_update_addon_status($_REQUEST['id'], $_REQUEST['status'])) !== true) {
        Registry::get('ajax')->assign('return_status', $res);
    }

    exit;

} elseif ($mode == 'manage') {

    $params = $_REQUEST;
    $params['for_company'] = (bool) Registry::get('runtime.company_id');

    list($addons, $search) = fn_get_addons($params);

    Registry::get('view')->assign('search', $search);
    Registry::get('view')->assign('addons_list', $addons);
}

/**
 * Gets addons list
 * @param array $params search params
 * @param int $items_per_page items per page for pagination
 * @param string $lang_code language code
 * @return array addons list and filtered search params
 */
function fn_get_addons($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    $params = LastView::instance()->update('addons', $params);

    $default_params = array(
        'type' => 'any',
    );

    $params = array_merge($default_params, $params);

    $addons = array();
    $sections =  Settings::instance()->getAddons();
    $all_addons = fn_get_dir_contents(Registry::get('config.dir.addons'), true, false);
    $installed_addons = db_get_hash_array(
        'SELECT a.addon, a.status, b.name as name, b.description as description, a.separate, a.unmanaged, a.has_icon '
        . 'FROM ?:addons as a LEFT JOIN ?:addon_descriptions as b ON b.addon = a.addon AND b.lang_code = ?s'
        . 'ORDER BY b.name ASC',
        'addon', $lang_code
    );

    foreach ($installed_addons as $key => $addon) {
        $installed_addons[$key]['has_sections'] = Settings::instance()->sectionExists($sections, $addon['addon']);
        $installed_addons[$key]['has_options'] = ($installed_addons[$key]['has_sections']) ? Settings::instance()->optionsExists($addon['addon'], 'ADDON') : false;


        // Check add-on snaphot
        if (!fn_check_addon_snapshot($key)) {
            $installed_addons[$key]['status'] = 'D';
            $installed_addons[$key]['snapshot_correct'] = false;
        } else {
            $installed_addons[$key]['snapshot_correct'] = true;
        }
    }

    foreach ($all_addons as $addon) {

        if (in_array($params['type'], array('any', 'installed', 'active', 'disabled'))) {

            $search_status = $params['type'] == 'active' ? 'A' : ($params['type'] == 'disabled' ? 'D' : '');

            if (!empty($installed_addons[$addon])) {
                // exclude unmanaged addons from the list
                if ($installed_addons[$addon]['unmanaged'] == true) {
                    continue;
                }

                if (!empty($search_status) && $installed_addons[$addon]['status'] != $search_status) {
                    continue;
                }

                $addons[$addon] = $installed_addons[$addon];

                fn_update_lang_objects('installed_addon', $addons[$addon]);

                // Generate custom description
                $func = 'fn_addon_dynamic_description_' . $addon;
                if (function_exists($func)) {
                    $addons[$addon]['description'] = $func($addons[$addon]['description']);
                }
            }
        }

        if (empty($installed_addons[$addon]) && empty($params['for_company']) && (in_array($params['type'], array('any', 'not_installed')))) {
            $addon_scheme = SchemesManager::getScheme($addon);
            if ($addon_scheme != false && !$addon_scheme->getUnmanaged()) {
                $addons[$addon] = array(
                    'status' => 'N', // Because it's not installed
                    'name' => $addon_scheme->getName(),
                    'snapshot_correct' => fn_check_addon_snapshot($addon),
                    'description' => $addon_scheme->getDescription(),
                    'has_icon' => $addon_scheme->hasIcon()
                );
            }
        }
    }

    if (!empty($params['q'])) {
        foreach ($addons as $addon => $addon_data) {
            if (!preg_match('/' . preg_quote($params['q'], '/') . '/ui', $addon_data['name'], $m)) {
                unset($addons[$addon]);
            }
        }
    }

    $addons = fn_sort_array_by_key($addons, 'name', SORT_ASC);

    return array($addons, $params);
}
