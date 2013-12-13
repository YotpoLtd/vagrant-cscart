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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/* POST data processing */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //
    // Processing multiple updating of page elements
    //
    /*if ($mode == 'm_update') {
        if (is_array($pages_description)) {
            foreach ($pages_description as $k => $v) {
                if (!empty($pages_description[$k]['page']) && isset($pages_description[$k]['seo_name'])) {
                    $page_data = fn_get_page_data($k, CART_LANGUAGE);
                    $object_name = (!empty($pages_description[$k]['seo_name'])) ? $pages_description[$k]['seo_name'] : $pages_description[$k]['page'];
                    fn_create_seo_name($page_data['page_inner_id'], "a", $object_name);
                }
            }
        }
    }*/

    return;
}

if ($mode == 'm_update') {
    if (!empty($selected_fields['seo_name'])) {
        $field_groups['A']['seo_name'] = 'pages_description';
        $filled_groups['A']['seo_name'] = __('seo_name');
    }
}
/* /POST data processing */
