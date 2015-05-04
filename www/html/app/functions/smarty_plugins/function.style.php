<?php

use Tygh\Debugger;
use Tygh\Development;
use Tygh\Less;
use Tygh\Themes\Presets;
use Tygh\Registry;
use Tygh\Storage;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_function_style($params, &$smarty)
{
    $params['src'] = !empty($params['src']) ? $params['src'] : '';
    $location = Registry::get('config.current_location') . (strpos($params['src'], '/') === 0 ? '' : ('/' . fn_get_theme_path('[relative]/[theme]') . '/css'));
    $url = $location . '/' . $params['src'];

    if (Development::isEnabled('dev_css') && strpos($params['src'], '.less') !== false) {
        $relative_path = fn_get_theme_path('[relative]/[theme]/css');
        $filename = fn_get_theme_path('[themes]/[theme]') . '/css/' . $params['src'];
        $rel_filename = $relative_path . '/' . $params['src'] . '-' .  Registry::get('runtime.layout.layout_id') . '.css';

        if (Development::isEnabled('compile_check') || Debugger::isActive()) {
            $abs_filename = Storage::instance('statics')->getAbsolutePath($rel_filename);
            if (file_exists($abs_filename)) {

                $preset_file = Presets::factory(fn_get_theme_path('[theme]'))->getPresetFile(Registry::get('runtime.layout.preset_id'));
                if (filemtime($filename) > filemtime($abs_filename)) {
                    $force_update = true;
                } elseif (file_exists($preset_file) && filemtime($preset_file) > filemtime($abs_filename)) {
                    $force_update = true;
                }
            }
        }

        if (!Storage::instance('statics')->isExist($rel_filename) || !empty($force_update)) {

            $less_output = fn_get_contents($filename);
            $less = new Less();
            $less->setImportDir(dirname($filename));

            Storage::instance('statics')->put($rel_filename, array(
                'contents' => $less->customCompile(fn_get_contents($filename), Storage::instance('statics')->getAbsolutePath(dirname($rel_filename))),
                'overwrite' => true
            ));

            fn_put_contents(fn_get_cache_path(false) . 'theme_editor/standalone.css', "\n" . $less_output, '', DEFAULT_FILE_PERMISSIONS, true);
        }

        $url = Storage::instance('statics')->getUrl($rel_filename);

    } elseif (!empty($params['content'])) {
        if (!Development::isEnabled('dev_css') || $params['type'] == 'css') {
            $content = $params['content'];
        } else {
            $less = new Less();
            $less->setImportDir(fn_get_theme_path('[themes]/[theme]/css', 'C'));
            $params['content'] = '@import "/lib/bootstrap/less/mixins.less";' . "\n" . $params['content'];
            $content = $less->customCompile($params['content'], Registry::get('config.dir.root'));
        }

        return '<style type="text/css"' . (!empty($params['media']) ? (' media="' . $params['media'] . '"') : '') .'>' . $content . '</style>';
    }

    return '<link type="text/css" rel="stylesheet"' . (!empty($params['media']) ? (' media="' . $params['media'] . '"') : '') .
           ' href="' . $url . '" />';

}
