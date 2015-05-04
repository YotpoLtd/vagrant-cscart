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
use Tygh\Less;
use Tygh\Themes\Patterns;
use Tygh\Themes\Presets;
use Tygh\Storage;
use Tygh\Settings;
use Tygh\BlockManager\Layout;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('MAX_BACKGROUND_SIZE', 204800); // 200kb

if ($_SERVER['REQUEST_METHOD'] == 'POST' && Registry::get('config.demo_mode')) {
    // Customer do not have rights to save presets in Demo mode

    fn_set_notification('W', __('warning'), __('error_demo_mode'));
    exit;
}

if (!Registry::get('runtime.customization_mode.theme_editor') && !Registry::get('runtime.customization_mode.design')) {
    fn_set_notification('E', __('error'), __('access_denied'));

    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'save') {

        fn_theme_editor_save_preset($_REQUEST['preset_id'], $_REQUEST['preset']);

        fn_attach_image_pairs('logotypes', 'logos');
    }

    return array(CONTROLLER_STATUS_OK, 'theme_editor.view');
}

if ($mode == 'view') {
    if (!empty($_REQUEST['preset_id'])) {
        fn_theme_editor_set_preset($_REQUEST['preset_id']);
    }

    fn_theme_editor($_REQUEST);

    Registry::get('view')->display('views/theme_editor/view.tpl');
    exit;

} elseif ($mode == 'delete_preset') {

    Presets::factory(fn_get_theme_path('[theme]', 'C'))->delete($_REQUEST['preset_id']);

    return array(CONTROLLER_STATUS_OK, 'theme_editor.view');

} elseif ($mode == 'get_css') {
    $css_filename = !empty($_REQUEST['css_filename']) ? fn_basename($_REQUEST['css_filename']) : '';
    $theme_name = fn_get_theme_path('[theme]', 'C');
    $content = '';

    if (!empty($css_filename)) {

        $content = fn_get_contents(fn_get_cache_path(false) . 'theme_editor/' . $css_filename);
        if (strpos($content, '#LESS') !== false) {
            list($css_content, $less_content) = explode('#LESS#', $content);
            $css_content = Less::parseUrls($css_content, Registry::get('config.dir.root'), fn_get_theme_path('[themes]/[theme]/media'));
        } else {
            $less_content = $content;
            $css_content = '';
        }

        $data = array();

        // If theme ID passed, set default theme
        if (!empty($_REQUEST['preset_id'])) {
            fn_theme_editor_set_preset($_REQUEST['preset_id']);

        // If theme elements passed, get them
        } elseif (!empty($_REQUEST['preset']['data'])) {
            $data = $_REQUEST['preset']['data'];
            $data = Presets::factory($theme_name)->processCopy('', $data);
        }

        $less = new Less();
        $less_content = preg_replace('/@import ".*?";/', '', $less_content); // we do not need import here now
        $content = $css_content . $less->customCompile($less_content, Registry::get('config.dir.root'), $data);

        // remove external fonts to avoid flickering when styles are reloaded
        //$content = preg_replace("/@font-face \{.*?\}/s", '', $content);
    }

    header('content-type: text/css');
    fn_echo($content);
    exit;


} elseif ($mode == 'duplicate') {

    if (!empty($_REQUEST['name']) && Presets::factory(fn_get_theme_path('[theme]', 'C'))->copy($_REQUEST['preset_id'], $_REQUEST['name'])) {
        fn_theme_editor_set_preset($_REQUEST['name']);
    } else {
        fn_set_notification('E', __('error'), __('theme_editor.preset_data_cannot_be_saved', array(
            '[theme_dir]' => fn_get_theme_path('[relative]/[theme]/presets')
        )));
    }

    return array(CONTROLLER_STATUS_OK, 'theme_editor.view');

}


function fn_theme_editor_set_preset($preset_id)
{
    $preset_id = fn_basename($preset_id);

    db_query("UPDATE ?:bm_layouts SET preset_id = ?s WHERE layout_id = ?i", $preset_id, Registry::get('runtime.layout.layout_id'));
    Registry::set('runtime.layout.preset_id', $preset_id);

    fn_clear_cache('statics', 'design/');

    return true;
}

function fn_theme_editor_save_preset($preset_id, $preset)
{
    $theme_name = fn_get_theme_path('[theme]', 'C');

    if (empty($preset_id) && !empty($preset['name'])) {
        $preset_id = $preset['name'];

        Presets::factory($theme_name)->copy(Registry::get('runtime.layout.preset_id'), $preset_id);
    }

    if (empty($preset) || empty($preset['data']) || empty($preset_id)) {
        return false;
    }

    // Attach patterns
    $uploaded_data = fn_filter_uploaded_data('backgrounds');
    if (!empty($uploaded_data)) {
        $preset = Patterns::save($preset_id, $preset, $uploaded_data);
    }

    // Save preset data
    if (!Presets::factory($theme_name)->update($preset_id, $preset)) {

        fn_set_notification('E', __('error'), __('theme_editor.preset_data_cannot_be_saved', array(
            '[theme_dir]' => fn_get_theme_path('[relative]/[theme]/presets')
        )));

        return false;
    }

    fn_theme_editor_set_preset($preset_id);

    return $preset_id;
}

function fn_theme_editor($params, $lang_code = CART_LANGUAGE)
{
    $view = Registry::get('view');

    $theme_name = Registry::get('runtime.layout.theme_name');
    $layout_id = Registry::get('runtime.layout.layout_id');

    if (!Registry::get('runtime.layout.preset_id')) {
        $default_preset_id = Presets::factory($theme_name)->getDefault();
        
        db_query('UPDATE ?:bm_layouts SET preset_id = ?s WHERE layout_id = ?i', $default_preset_id, $layout_id);
        Registry::set('runtime.layout.preset_id', $default_preset_id);
    }

    $preset_id = Registry::get('runtime.layout.preset_id');

    // get current preset
    $current_preset = Presets::factory($theme_name)->get($preset_id, true);

    // get all presets
    $presets_list = Presets::factory($theme_name)->getList();

    $schema = Presets::factory($theme_name)->getSchema();
    $sections = array(
        'te_general' => 'theme_editor.general',
        'te_logos' => 'theme_editor.logos',
        'te_colors' => 'theme_editor.colors',
        'te_fonts' => 'theme_editor.fonts',
        'te_backgrounds' => 'theme_editor.backgrounds',
        'te_css' => 'theme_editor.css',
    );

    foreach ($sections as $section_id => $section) {
        if ($section_id == 'te_logos') { // Logos is hardcoded section, no need to define it in schema
            continue;
        }
        $section_id = str_replace('te_', '', $section_id);
        if (!isset($schema[$section_id])) {
            unset($sections['te_' . $section_id]);
        }
    }

    if (empty($params['selected_section']) || !isset($sections[$params['selected_section']])) {
        reset($sections);
        $params['selected_section'] = key($sections);
    }

    $view->assign('cse_logo_types', fn_get_logo_types());
    $view->assign('cse_logos', fn_get_logos(Registry::get('runtime.company_id')));
    $view->assign('selected_section', $params['selected_section']);
    $view->assign('te_sections', $sections);
    $view->assign('props_schema', $schema);
    $view->assign('current_preset', $current_preset);
    $view->assign('theme_patterns', Patterns::get($preset_id));
    $view->assign('presets_list', $presets_list);
    $view->assign('manifest', Presets::factory($theme_name)->getManifest());

    $view->assign('layouts', Layout::instance()->getList(array(
        'theme_name' => $theme_name
    )));
    $view->assign('layout_data', Layout::instance()->get($layout_id));
    $view->assign('theme_url', fn_url(empty($params['theme_url']) ? '' : $params['theme_url']));
}
