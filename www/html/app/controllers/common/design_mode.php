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

if (!Registry::get('runtime.customization_mode.design') && !Registry::get('runtime.customization_mode.translation')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update_customization_mode') {

        fn_update_customization_mode($_REQUEST['customization_modes']);

        return array(CONTROLLER_STATUS_OK, $_REQUEST['current_url']);
    }
}

if ($mode == 'update_langvar') {
    fn_trusted_vars('langvar_value');
    $name = empty($_REQUEST['langvar_name']) ? '' : fn_strtolower($_REQUEST['langvar_name']);
    $table = 'language_values';
    $update_fields = array();
    $condition = array();

    if (strpos($name, '-') !== false) {
        $params = explode('-', $name);
        $table = $params[0];
        for ($i = 2; $i < count($params); $i += 2) {
            $condition[$params[$i]] = $params[$i+1];
        }
        $condition['lang_code'] = $_REQUEST['lang_code'];
        $update_fields[] = db_quote($params[1] . ' = ?s', $_REQUEST['langvar_value']);
    } else {
        $update_fields[] = db_quote('value = ?s', $_REQUEST['langvar_value']);
        $condition['name'] = $_REQUEST['langvar_name'];
        $condition['lang_code'] = $_REQUEST['lang_code'];
    }

    fn_set_hook('translation_mode_update_langvar', $table, $update_fields, $condition);

    db_query('UPDATE ?:' . $table . ' SET ' . implode(', ', $update_fields) . ' WHERE ?w', $condition);

    exit;

} elseif ($mode == 'get_langvar') {
    $name = empty($_REQUEST['langvar_name']) ? '' : fn_strtolower($_REQUEST['langvar_name']);
    if (strpos($name, '-') !== false) {
        $params = explode('-', $name);
        $where = array();
        for ($i = 2; $i < count($params); $i += 2) {
            $where[$params[$i]] = $params[$i+1];
        }
        $where['lang_code'] = $_REQUEST['lang_code'];
        Registry::get('ajax')->assign('langvar_value', db_get_field("SELECT $params[1] FROM ?:$params[0] WHERE ?w", $where));
    } else {
        Registry::get('ajax')->assign('langvar_value', __($name, '', $_REQUEST['lang_code']));
    }
    exit;

} elseif ($mode == 'get_content') {
    $ext = fn_strtolower(fn_get_file_ext($_REQUEST['file']));

    if ($ext == 'tpl') {
        $theme_path = fn_get_theme_path('[themes]/[theme]/templates/', 'C');

        Registry::get('ajax')->assign('content', fn_get_contents($_REQUEST['file'], $theme_path));
    }
    exit;

} elseif ($mode == 'save_template') {
    fn_trusted_vars('content');

    $ext = fn_strtolower(fn_get_file_ext($_REQUEST['file']));
    if ($ext == 'tpl') {
        $theme_path = fn_get_theme_path('[themes]/[theme]/templates/', 'C');

        if (fn_put_contents($_REQUEST['file'], $_REQUEST['content'], $theme_path)) {
            fn_set_notification('N', __('notice'), str_replace("[file]", fn_basename($_REQUEST['file']), __('text_file_saved')));
        }
    }

    return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['current_url']);

} elseif ($mode == 'restore_template') {
    $copied = false;

    $full_path = fn_get_theme_path('[themes]/[theme]', 'C') . '/templates/' . $_REQUEST['file'];

    if (fn_check_path($full_path)) {

        $c_name = fn_normalize_path($full_path);
        $r_name = fn_normalize_path(Registry::get('config.dir.themes_repository') . 'basic/templates/' . $_REQUEST['file']);

        if (is_file($r_name)) {
            $copied = fn_copy($r_name, $c_name);
        }

        if ($copied) {
            fn_set_notification('N', __('notice'), __('text_file_restored', array(
                '[file]' => fn_basename($_REQUEST['file'])
            )));
        } else {
            fn_set_notification('E', __('error'), __('text_cannot_restore_file', array(
                '[file]' => fn_basename($_REQUEST['file'])
            )));
        }

        if ($copied) {
            if (defined('AJAX_REQUEST')) {
                Registry::get('ajax')->assign('force_redirection', fn_url($_REQUEST['current_url']));
                Registry::get('ajax')->assign('non_ajax_notifications', true);
            }

            return array(CONTROLLER_STATUS_OK, $_REQUEST['current_url']);
        }
    }
    exit;

}
