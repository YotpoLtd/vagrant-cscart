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

/**
 * Get item extra information
 * @param array $data extra data
 * @return json-encoded data on success or empty string on failure
 */
function fn_exim_orders_get_extra($data)
{
    if (!empty($data)) {
        $data = @unserialize($data);
        return fn_exim_json_encode($data);
    }

    return '';
}

/**
 * Set extra information
 * @param array $ids item ids (order_id/item_id)
 * @param array $data data to set
 * @return bool true on success, false otherwise
 */
function fn_exim_orders_set_extra($ids, $data)
{
    $data = json_decode($data, true);

    if (!is_array($data)) {
        return false;
    }

    $data = serialize($data);
    $insert = array(
        'extra' => $data,
    );

    db_query("UPDATE ?:order_details SET ?u WHERE order_id = ?i AND item_id = ?i", $insert, $ids['order_id'], $ids['item_id']);

    return true;
}
