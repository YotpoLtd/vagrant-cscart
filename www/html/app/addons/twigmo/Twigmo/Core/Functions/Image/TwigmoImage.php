<?php

namespace Twigmo\Core\Functions\Image;

use Tygh\Registry;

class TwigmoImage
{
    /*
     * Update additional images
     */
    final public static function updateImagesByApiData(
        $images,
        $object_id = 0,
        $object_type = 'product',
        $lang_code = CART_LANGUAGE
    ) {
        $icons = array();
        $detailed = array();
        $pair_data = array();

        foreach ($images as $image) {
            $p_data = array (
                'pair_id' => 0,
                'type' => 'A',
                'image_alt' => '',
                'detailed_alt' => !empty($image['alt']) ? $image['alt'] : '',
            );

            if (!empty($image['image_id'])) {
                $image_info = db_get_row(
                    "SELECT type, pair_id
                     FROM ?:images_links
                     WHERE object_id = ?i
                     AND object_type=?s
                     AND detailed_id = ?i",
                    $object_id,
                    $object_type,
                    $image['image_id']
                );

                if (empty($image_info) || $image_info['type'] == 'M') {
                    // ignore errors in image_id
                    // deny update/delete main detailed image
                    continue;
                }

                if (!empty($image['deleted']) && $image['deleted'] == 'Y') {
                    fn_delete_image($image['image_id'], $image_info['pair_id'], 'detailed');
                    continue;
                }

                $p_data['pair_id'] = $image_info['pair_id'];
                $p_data['image_alt'] = db_get_field(
                    "SELECT a.description
                     FROM ?:common_descriptions as a, ?:images_links as b
                     WHERE a.object_holder = ?s
                     AND a.lang_code = ?s
                     AND a.object_id = b.image_id
                     AND b.pair_id = ?i",
                    'images',
                    $lang_code,
                    $image_info['pair_id']
                );
            }

            $detailed_image = self::fn_twg_get_image_by_api_data($image);
            if (empty($image['image_id']) && empty($detailed_image)) {
                continue;
            }
            $detailed[] = $detailed_image;
            $pair_data[] = $p_data;
        }

        return fn_update_image_pairs(
            $icons,
            $detailed,
            $pair_data,
            $object_id,
            $object_type,
            array(),
            '',
            0,
            true,
            $lang_code
        );
    }

    final public static function updateIconsByApiData(
        $image,
        $object_id = 0,
        $object_type = 'product',
        $lang_code = CART_LANGUAGE
    ) {
        if (!empty($image['deleted']) && $image['deleted'] == 'Y') {
            // delete image
            $image_info = db_get_row(
                "SELECT image_id, pair_id
                 FROM ?:images_links
                 WHERE object_id = ?i
                 AND object_type=?s AND type = 'M'",
                $object_id,
                $object_type
            );

            if (!empty($image_info)) {
                fn_delete_image($image_info['image_id'], $image_info['pair_id'], $object_type);
            }

            return true;
        }

        $icon_list = array();

        if ($icon = self::fn_twg_get_image_by_api_data($image)) {
            $icon_list[] = $icon;
        }

        $detailed_alt = db_get_field(
            "SELECT a.description
             FROM ?:common_descriptions as a, ?:images_links as b
             WHERE a.object_holder = ?s
             AND a.lang_code = ?s
             AND a.object_id = b.detailed_id
             AND b.object_id = ?i
             AND b.object_type = ?s
             AND b.type = ?s",
            'images',
            $lang_code,
            $object_id,
            $object_type,
            'M'
        );

        $icon_data = array (
            'type' => 'M',
            'image_alt' => !empty($image['alt']) ? $image['alt'] : '',
            'detailed_alt' => $detailed_alt
        );

        return fn_update_image_pairs(
            $icon_list,
            array(),
            array($icon_data),
            $object_id,
            $object_type,
            array(),
            '',
            0,
            true,
            $lang_code
        );
    }

    final public static function getApiImageData(
        $image_pair,
        $type       = 'product',
        $image_type = 'icon',
        $params     = array()
    )
    {
        if (
            $image_type == 'detailed'
            and !empty($image_pair['detailed_id'])
            or empty($image_pair['image_id'])
            or !empty($image_pair['image_id'])
            and empty($image_pair['icon'])
        ) {
            $icon = isset($image_pair['detailed']) ? $image_pair['detailed'] : array();
            $icon['image_id'] = $image_pair['detailed_id'];
        } elseif (!empty($image_pair['image_id'])) {
            $icon = $image_pair['icon'];
            $icon['image_id'] = $image_pair['image_id'];
        }

        if (
            isset($params['width'])
            and isset($params['height'])
            and (!isset($params['keep_small_images'])
            or $icon['image_x'] > $params['width']
            or $icon['image_y'] > $params['height'])
        ) {
            $icon['url'] = fn_generate_thumbnail(
                version_compare(PRODUCT_VERSION, '4.0.2', '<')? $icon['image_path']: $icon['relative_path'],
                $params['width'],
                $params['height'],
                !isset($params['keep_small_images'])
            );
            $_path = str_replace(Registry::get('config.http_path') . '/', '', $icon['url']);
            $real_path = htmlspecialchars_decode(Registry::get('config.dir.root') . '/' . $_path, ENT_QUOTES);
            $size = fn_get_image_size($real_path);
            $icon['image_y'] = $size ? $size[0] : $params['height'];
            $icon['image_x'] = $size ? $size[1] : $params['width'];
        } else {
            $icon['url'] = $icon['image_path'];
        }

        if (!empty($params['use_bin_data'])) {
            $icon = self::getApiImageBinData($icon, $params, $type);
        }

        if (AREA == 'A' or TWIGMO_IS_NATIVE_APP) {
            $icon['url'] =
            (defined('HTTPS') ?
                'https://' . Registry::get('config.http_host') :
                 'http://' . Registry::get('config.https_host')
            ) . $icon['url'];
        }

        // Delete unnecessary fields
        if (isset($icon['absolute_path'])) {
            unset($icon['absolute_path']);
        }

        return $icon;
    }

    /**
     * Get customer images path
     */
    final public static function getImagesPath()
    {
        $theme_path = fn_get_theme_path(
                '[relative]/[theme]',
                'C',
                Registry::get('runtime.company_id')
        );

        $path =
            $theme_path
            . '/media/images/';

        return $path;
    }

    /**
     * Get default logo's url for twigmo
     */
    final public static function getDefaultLogoUrl()
    {
        $logos = fn_get_logos();

        return !empty($logos['theme']['image']['image_path'])?
                $logos['theme']['image']['image_path']:
                '';
    }

    /**
     * Get favicon's url for twigmo
     */
    final public static function getFaviconUrl()
    {
        list($mobile_script_url,) = fn_twg_get_mobile_scripts_url('default');

        return Registry::get('addons.twigmo.faviconURL') ?
                Registry::get('addons.twigmo.faviconURL') :
                $mobile_script_url . '/images/apple-touch-icon.png';
    }

    /*
    * Extract image from api data
    */
    private static function fn_twg_get_image_by_api_data($api_image)
    {
        if (empty($api_image['data']) || (empty($api_image['file_name']) && empty($api_image['type']))) {
            return false;
        }

        if (empty($api_image['file_name'])) {
            $api_image['file_name'] = 'image_' . strtolower(fn_generate_code('', 4)) . '.' . $api_image['type'];
        }

        $_data = base64_decode($api_image['data']);

        $image = array (
            'name' => $api_image['file_name'],
            'path' => fn_create_temp_file(),
            'size' => strlen($_data)
        );

        $file_descriptor = fopen($image['path'], 'wb');

        if (!$file_descriptor) {
            return false;
        }

        fwrite($file_descriptor, $_data, $image['size']);
        fclose($file_descriptor);
        @chmod($image['path'], DEFAULT_FILE_PERMISSIONS);

        return $image;
    }

    final private static function getApiImageBinData($icon, $params, $type = 'product')
    {
        if (!empty($icon['absolute_path'])) {
            $image_file = $icon['absolute_path'];
        } else {
            $_image_file = db_get_field("SELECT image_path FROM ?:images WHERE image_id = ?i", $params['image_id']);
            $image_file = Registry::get('config.dir.images'). $type . '/' . $_image_file;
        }

        if (extension_loaded('gd') && (!empty($params['image_x']) || !empty($params['image_y']))) {
            $new_image_x = !empty($params['image_x']) ?
            $params['image_x'] : $params['image_y'] / $icon['image_y'] * $icon['image_x'];
            $new_image_y = !empty($params['image_y']) ?
            $params['image_y'] : $params['image_x'] / $icon['image_x'] * $icon['image_y'];

            $new_image_gd = imagecreatetruecolor($new_image_x, $new_image_y);
            list(,,$mime_type) = fn_get_image_size($image_file);
            $ext = fn_get_image_extension($mime_type);

            if ($ext == 'gif' && function_exists('imagegif')) {
                $image_gd = imagecreatefromgif($image_file);
            } elseif ($ext == 'jpg' && function_exists('imagejpeg')) {
                $image_gd = imagecreatefromjpeg($image_file);
            } elseif ($ext == 'png' && function_exists('imagepng')) {
                $image_gd = imagecreatefrompng($image_file);
            } else {
                return false;
            }

            imagecopyresized(
                $new_image_gd,
                $image_gd,
                0,
                0,
                0,
                0,
                $new_image_x,
                $new_image_y,
                $icon['image_x'],
                $icon['image_y']
            );

            $tmp_file = fn_create_temp_file();
            if ($ext == 'gif') {
                imagegif($new_image_gd, $tmp_file);
            } elseif ($ext == 'jpg') {
                imagejpeg($new_image_gd, $tmp_file, 50);
            } elseif ($ext == 'png') {
                imagepng($new_image_gd, $tmp_file, 0);
            }

            if (!($image_data = fn_get_contents($tmp_file))) {
                return false;
            }

            $icon['data'] = base64_encode($image_data);
            $icon['image_x'] = $new_image_x;
            $icon['image_y'] = $new_image_y;

        } elseif (fn_get_contents($image_file)) {
            $image_data = fn_get_contents($image_file);
            $icon['data'] = base64_encode($image_data);
        }

        return $icon;
    }

}
