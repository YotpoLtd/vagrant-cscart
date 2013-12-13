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

namespace Tygh\Themes;

use Tygh\Less;
use Tygh\Themes\Patterns;
use Tygh\Registry;

class Presets
{
    private static $instances = array();

    private $theme_name = '';
    private $schema = array();

    private $gfonts_tag = 'GFONTS';

    public function __construct($theme_name)
    {
        $this->theme_name = $theme_name;

        $path = fn_get_theme_path('[themes]/' . $theme_name . '/presets/', 'C');
        if (file_exists($path . 'schema.json')) {
            $schema = fn_get_contents($path . 'schema.json');
            $this->schema = json_decode($schema, true);
        } else {
            $this->schema = array();
        }
    }

    /**
     * Gets list of presets
     *
     * @return array List of available presets
     */
    public function getList()
    {
        $presets = array();

        $theme_path = $this->getPresetsPath();

        if (is_dir($theme_path)) {
            $preset_files = fn_get_dir_contents($theme_path, false, true, 'less');
        }

        if (!empty($preset_files)) {
            foreach ($preset_files as $id => $preset_id) {
                $preset_id = fn_basename($preset_id, '.less');
                $presets[$preset_id] = self::get($preset_id);
            }
        }

        return $presets;
    }

    /**
     * Gets full preset information
     *
     * @param  string  $preset_id File name of the preset schema (like: "satori")
     * @param  boolean $parse     parse less to variables if true
     * @return array   Preset information
     */
    public function get($preset_id, $parse = false)
    {
        $preset_id = fn_basename($preset_id);
        $preset = array();
        $data = array();
        $parsed = array();
        $less_content = fn_get_contents($this->getPresetFile($preset_id));

        if (!empty($less_content)) {

            if ($parse) {
                $less = new Less();
                $data = $less->extractVars($less_content);
                $parsed = $this->cssToUrl($data);
            }

            $custom_css = $this->getCustomCss($preset_id);

            $preset = array(
                'preset_id' => $preset_id,
                'data' => $data,
                'name' => $preset_id,
                'less' => $less_content,
                'custom_css' => $custom_css,
                'parsed' => $parsed
            );
        }

        return $preset;
    }

    /**
     * Saves less data to preset file
     *
     * @param string $preset_id File name of the preset schema (like: "satori")
     * @param array  $preset    preset
     *
     * @return boolean false on failure, true on success
     */
    public function update($preset_id, $preset)
    {
        $preset_id = fn_basename($preset_id);
        $preset_path = $this->getPresetFile($preset_id);

        $current_preset = $this->get($preset_id);
        $less = empty($current_preset['less']) ? '' : $current_preset['less'];

        $preset['data'] = $this->processCopy($preset_id, $preset['data']);

        foreach ($preset['data'] as $var_name => $value) {

            $less_var = Less::arrayToLessVars(array($var_name => $value));

            if (preg_match('/@' . $var_name . ':.*?;/m', $less)) {
                $less = preg_replace('/@' . $var_name . ':.*?;$/m', $less_var, $less);
            } else {
                $less .= $less_var;
            }
        }

        $less = $this->addGoogleFonts($preset['data'], $less);

        $this->addCustomCss($preset_id, $preset['custom_css']);

        return fn_put_contents($preset_path, $less);
    }

    /**
     * Deletes preset
     * @param  string  $preset_id preset ID
     * @return boolean true on succes, false otherwise
     */
    public function delete($preset_id)
    {
        $preset_id = fn_basename($preset_id);

        if (fn_rm($this->getPresetFile($preset_id))) {
            fn_rm($this->getPresetFile($preset_id, true)); // remove custom css
            fn_rm(Patterns::getPath($preset_id));

            return true;
        }

        return false;
    }

    /**
     * Gets default preset name
     *
     * @return string Preset name (like: satori)
     */
    public function getDefault()
    {
        $manifest = self::getManifest();

        return !empty($manifest['default_preset']) ? $manifest['default_preset'] : '';
    }

    /**
     * Gets manifest information
     *
     * @return array Manifest data
     */
    public function getManifest()
    {
        $manifest = array();
        $manifest_path = fn_get_theme_path('[themes]/' . $this->theme_name . '/presets/manifest.json', 'C');

        if (is_file($manifest_path)) {
            $manifest = json_decode(fn_get_contents($manifest_path), true);
        }

        return $manifest;
    }

    /**
     * Processes data copy according to schema
     * @param  string $preset_id preset ID
     * @param  array  $data      preset
     * @return return preset
     */
    public function processCopy($preset_id, $data)
    {
        if (!empty($data['copy'])) {
            foreach ($this->schema['backgrounds']['fields'] as $field_id => $field_data) {
                if (empty($field_data['copies'])) {
                    continue;
                }

                foreach ($field_data['copies'] as $property => $fields) {
                    foreach ($fields as $field) {
                        if (!empty($data['copy'][$property][$field_id])) {
                            if (!empty($field['inverse'])) {
                                $data[$field['match']] = $field['default'];
                            } elseif (isset($data[$field['source']])) {
                                $data[$field['match']] = $data[$field['source']];
                            }
                        } else {
                            if (empty($field['inverse'])) {
                                $data[$field['match']] = $field['default'];
                            }
                        }
                    }
                }
            }
        }

        unset($data['copy']);

        $data = $this->urlToCss($preset_id, $data);

        return $data;
    }

    /**
     * Gets preset schema
     * @return array preset schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Copy preset
     * @param  string  $from preset ID to copy from
     * @param  string  $to   preset ID to copy to
     * @return boolean true on success, false otherwise
     */
    public function copy($from, $to)
    {
        $from = fn_basename($from);
        $to = fn_basename($to);

        $preset_file_from = $this->getPresetFile($from);
        $preset_file_to = $this->getPresetFile($to);

        if (is_file($preset_file_from)) {
            if (fn_copy($preset_file_from, $preset_file_to)) {

                $preset_css_from = $this->getPresetFile($from, true);
                if (file_exists($preset_css_from)) {
                    fn_copy($preset_css_from, $this->getPresetFile($to, true));
                }

                fn_copy(Patterns::getPath($from), Patterns::getPath($to));
                $content = fn_get_contents($preset_file_to);
                $content = str_replace('/patterns/' . $from . '/', '/patterns/' . $to . '/', $content);
                fn_put_contents($preset_file_to, $content);

                return true;
            }

        }

        return false;
    }

    /**
     * Gets preset LESS code
     * @param  array  $current_preset preset data to override current preset data
     * @return string preset LESS code
     */
    public function getLess($current_preset = array())
    {
        $custom_less = '';

        $preset = $this->get(Registry::get('runtime.layout.preset_id'));

        if (!empty($preset['less'])) {
            $custom_less = $preset['less'];
            $custom_less .= "\n" . $preset['custom_css'];
        }

        if (!empty($current_preset)) {
            $custom_less .= Less::arrayToLessVars($current_preset);
        }

        return $custom_less;
    }

    /**
     * Gets preset file path
     * @param  string  $preset_id preset ID
     * @param  boolean $css       gets custom css file is set to true
     * @return string  preset file path
     */
    public function getPresetFile($preset_id, $css = false)
    {
        return $this->getPresetsPath() . '/' . $preset_id . ($css == true ? '.css' : '.less');
    }

    /**
     * Gets presets path
     * @return string presets path
     */
    private function getPresetsPath()
    {
        return fn_get_theme_path('[themes]/' . $this->theme_name . '/presets/data', 'C');
    }

    /**
     * Gets custom CSS code from LESS code
     * @param  string $preset_id preset ID
     * @return string custom CSS code
     */
    private function getCustomCss($preset_id)
    {
        $file = $this->getPresetFile($preset_id, true);
        if (file_exists($file)) {
            return fn_get_contents($file);
        }

        return '';
    }

    /**
     * Adds custom css to preset LESS
     * @param  string  $preset_id  preset ID
     * @param  string  $custom_css CSS code
     * @return integer custom CSS length, written to file, boolean false on error
     */
    private function addCustomCss($preset_id, $custom_css)
    {
        return fn_put_contents($this->getPresetFile($preset_id, true), $custom_css);
    }

    /**
     * Adds Google Font initialization to preset LESS
     * @param  array  $preset_data preset data
     * @param  string $less        preset LESS code
     * @return string preset LESS code
     */
    private function addGoogleFonts($preset_data, $less)
    {
        $content = array();

        $less = preg_replace("#/\*{$this->gfonts_tag}\*/(.*?)/\*/{$this->gfonts_tag}\*/#s", '', $less);

        foreach ($this->schema['fonts']['fields'] as $field => $data) {
            if (empty($this->schema['fonts']['families'][$preset_data[$field]])) {
                // Google font!
                if (empty($content[$preset_data[$field]])) {
                    $css = fn_get_contents('http://fonts.googleapis.com/css?family=' . $preset_data[$field]);
                    if (!empty($css)) {
                        $content[$preset_data[$field]] = $css;
                    }
                }
            }
        }

        if (!empty($content)) {
            $less .= "\n/*{$this->gfonts_tag}*/" . "\n" . implode("\n", $content) . "\n/*/{$this->gfonts_tag}*/";
        }

        return $less;
    }

    /**
     * Converts CSS property ( url("../a.png") ) to URL (http://e.com/a.png)
     * @param  array $preset_data preset data
     * @return array modified parsed preset data vars
     */
    private function cssToUrl($preset_data)
    {
        $url = Registry::get('config.current_location') . '/' . fn_get_theme_path('[relative]/[theme]/');
        $parsed = array();
        if (!empty($this->schema['backgrounds']['fields'])) {
            foreach ($this->schema['backgrounds']['fields'] as $field) {
                if (!empty($field['properties']['pattern'])) {
                    $var_name = $field['properties']['pattern'];

                    if (!empty($preset_data[$var_name]) && strpos($preset_data[$var_name], 'url(') !== false) {
                        $parsed[$var_name] = preg_replace('/url\([\'"]?\.\.\/(.*?)[\'"]?\)/', $url . '$1', $preset_data[$var_name]);
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * Converts URL (http://e.com/a.png) to CSS property ( url("../a.png") )
     * @param  string $preset_id   preset ID
     * @param  array  $preset_data preset data (fields)
     * @return array  modified preset data
     */
    private function urlToCss($preset_id, $preset_data)
    {
        $patterns_url = Registry::get('config.current_location') . '/' . fn_get_theme_path('[relative]/[theme]');

        if (!empty($this->schema['backgrounds']['fields'])) {
            foreach ($this->schema['backgrounds']['fields'] as $field) {
                if (!empty($field['properties']['pattern'])) {
                    $var_name = $field['properties']['pattern'];

                    if (!empty($preset_data[$var_name]) && strpos($preset_data[$var_name], '//') !== false) {

                        $url = preg_replace('/url\([\'"]?(.*?)[\'"]?\)/', '$1', $preset_data[$var_name]);
                        if (strpos($url, '//') === 0) {
                            $url = 'http:' . $url;
                        }

                        if (strpos($url, $patterns_url) !== false) {
                            $url = str_replace($patterns_url, '..', $url);
                        } elseif ($preset_id) { // external url
                            $content = fn_get_contents($url);
                            $filename = basename($url);

                            fn_put_contents(Patterns::getPath($preset_id) . '/' . $var_name . '.' . fn_get_file_ext($filename), $content);

                            $url = Patterns::getRelPath($preset_id) . '/' . $var_name . '.' . fn_get_file_ext($filename);
                        }

                        $preset_data[$var_name] = 'url(' . $url . '?' . TIME . ')';
                    }
                }
            }
        }

        return $preset_data;
    }

    public static function factory($theme_name)
    {
        if (empty(self::$instances[$theme_name])) {
            self::$instances[$theme_name] = new self($theme_name);
        }

        return self::$instances[$theme_name];
    }
}
