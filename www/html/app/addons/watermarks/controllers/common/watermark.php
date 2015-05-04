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

use Tygh\Storage;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Delete image
//
if ($mode == 'create') {

    $result_image = '';

    if (!empty($_SERVER['REQUEST_URI'])) {

        list(, $image_file) = explode(WATERMARKS_DIR_NAME, $_SERVER['REQUEST_URI']);
        $watermarked_file = WATERMARKS_DIR_NAME . $image_file;

        if (Storage::instance('images')->isExist($watermarked_file)) {
            $result_image = Storage::instance('images')->getUrl($watermarked_file);

        } elseif (Storage::instance('images')->isExist($image_file)) {
            $image_name = fn_basename($image_file);
            $image_id = db_get_field("SELECT image_id FROM ?:images WHERE image_path LIKE ?l", "%$image_name%");
            $image_link = db_get_row("SELECT * FROM ?:images_links WHERE image_id = ?i OR detailed_id = ?i", $image_id, $image_id);

            if (!empty($image_link)) {
                $is_detailed = ($image_link['detailed_id'] == $image_id);
                $image_type = $is_detailed ? 'detailed' : 'icons';

                if (fn_watermark_create($image_file, $watermarked_file, $is_detailed)) {
                    $result_image = Storage::instance('images')->getUrl($watermarked_file);
                }
            }
        }
    }

    if (!empty($result_image)) {
        header('Location: ' . $result_image);
    }
    exit;

}
