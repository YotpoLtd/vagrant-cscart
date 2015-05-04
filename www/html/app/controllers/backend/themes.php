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

use Tygh\BlockManager\Exim;
use Tygh\BlockManager\Layout;
use Tygh\Themes\Presets;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Storage;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'clone') {

        $theme_src = basename($_REQUEST['theme_data']['theme_src']);
        $theme_dest = basename($_REQUEST['theme_data']['theme_dest']);
        $theme_path = fn_get_theme_path('[themes]/' . $theme_dest, 'C');

        if (!file_exists($theme_path)) {

            if (fn_install_theme_files($theme_src, $theme_dest, false)) {

                // Export current layout
                fn_put_contents($theme_path . '/layouts.xml', Exim::instance()->export());

                // Update manifest
                $manifest = parse_ini_file($theme_path . '/' . THEME_MANIFEST);
                $manifest['title'] = $_REQUEST['theme_data']['title'];
                $manifest['description'] = $_REQUEST['theme_data']['description'];

                // Put logos of current layout to manifest
                $logos = fn_get_logos(Registry::get('runtime.company_id'));
                foreach ($logos as $type => $logo) {
                    $filename = fn_basename($logo['image']['relative_path']);
                    Storage::instance('images')->export($logo['image']['relative_path'], $theme_path . '/media/images/' . $filename);
                    $manifest[$type] = 'media/images/' . $filename;
                }

                fn_write_ini_file($theme_path . '/' . THEME_MANIFEST, $manifest);

                fn_themes_new_layout($theme_dest, Registry::get('runtime.company_id'));                
            }

        } else {
            fn_set_notification('W', __('warning'), __('warning_theme_clone_dir_exists'));
        }

    }

    return array(CONTROLLER_STATUS_OK, 'themes.manage');
}

if ($mode == 'install') {
    if (!empty($_REQUEST['theme_name'])) {

        // Copy theme files to design/themes directory
        fn_install_theme_files($_REQUEST['theme_name'], $_REQUEST['theme_name']);
    }

    return array(CONTROLLER_STATUS_OK, 'themes.manage?selected_section=general');

} elseif ($mode == 'delete') {

    fn_delete_theme($_REQUEST['theme_name']);

    return array(CONTROLLER_STATUS_REDIRECT, 'themes.manage');

} elseif ($mode == 'set') {

    $is_exist = Layout::instance()->getList(array(
        'theme_name' => $_REQUEST['theme_name']
    ));

    if (empty($is_exist)) {
        // Create new layout
        fn_themes_new_layout($_REQUEST['theme_name'], Registry::get('runtime.company_id'));

    } else {
        Settings::instance()->updateValue('theme_name', $_REQUEST['theme_name'], '', true, Registry::get('runtime.company_id'));
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'themes.manage');

} elseif ($mode == 'manage') {

    Registry::get('view')->assign('available_themes', fn_get_available_themes(Registry::get('settings.theme_name')));
    Registry::get('view')->assign('themes_prefix', fn_get_theme_path('[relative]', 'C'));
    Registry::get('view')->assign('repo_prefix', fn_get_theme_path('[repo]', 'C'));

    Registry::set('navigation.tabs', array(
        'general' => array (
            'title' => __('general'),
            'js' => true
        ),
        'install_themes' => array (
            'title' => __('install_themes'),
            'js' => true
        )
    ));

    $theme_name = Settings::instance()->getValue('theme_name', '');
    $layout = Layout::instance()->get();

    $preset = Presets::factory($theme_name)->get($layout['preset_id']);
    $manifest = Presets::factory($theme_name)->getManifest();

    if (!empty($manifest['names'][$layout['preset_id']])) {
        $layout['preset_name'] = $manifest['names'][$layout['preset_id']];
    } else {
        $layout['preset_name'] = empty($preset['name']) ? '--' : $preset['name'];
    }
    

    Registry::get('view')->assign('layout', $layout);
}

/**
 * Creates layout and installs theme
 *
 * @param string $theme_name theme name
 * @param integer $company_id company ID
 * @return boolean true if theme installed successfully, false - otherwise
 */
function fn_themes_new_layout($theme_name, $company_id = null)
{
    $preset_id = Presets::factory($theme_name)->getDefault();

    // Create new layout
    $layout_id = Layout::instance()->update(array(
        'name' => __('main') . ' (' . $theme_name . ')',
        'theme_name' => $theme_name,
        'is_default' => 1,
        'preset_name' => $preset_id,
    ));

    return fn_install_theme($layout_id, $theme_name, $company_id);
}
