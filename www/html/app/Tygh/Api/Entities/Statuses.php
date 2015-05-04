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

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;

class Statuses extends AEntity
{
    public function index($id = '', $params = array())
    {
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        $type = (!empty($params['type'])) ? $params['type'] : STATUSES_ORDER;

        if (!empty($id)) {
            $data = fn_get_status_by_id($id, $lang_code);

            if (empty($data)) {
                $status = Response::STATUS_NOT_FOUND;
            } else {
                $status = Response::STATUS_OK;
            }
        } else {
            $data = fn_get_statuses($type, array(), false, false, $lang_code);
            $data = array_values($data);
            $status = Response::STATUS_OK;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = array();

        if (empty($params['type'])) {
            $params['type'] = STATUSES_ORDER;
        }

        if (empty($status_data) && !empty($params['description'])) {
            unset($params['status_id']);
            unset($params['status']);
            $status_name = fn_update_status('', $params, $params['type']);
            $status_data = fn_get_status_data($status_name, $params['type']);

            if ($status_data) {
                $status = Response::STATUS_CREATED;
                $data = array(
                    'status_id' => $status_data['status_id']
                );
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function update($id, $params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = array();

        unset($params['status_id']);

        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);
        $status_data = fn_get_status_by_id($id, $lang_code);

        if (empty($status_data)) {
            $status = Response::STATUS_NOT_FOUND;
            fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('status'))),'','404');

        } else {

            $params['status'] = $status_data['status'];
            $status_name = fn_update_status($status_data['status'], $params, $status_data['type']);

            if ($status_name) {
                $status = Response::STATUS_OK;
                $data = array(
                    'status_id' => $id
                );
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function delete($id)
    {
        $data = array();
        $status = Response::STATUS_BAD_REQUEST;

        $status_data = fn_get_status_by_id($id, DEFAULT_LANGUAGE);

        if (empty($status_data)) {
            $status = Response::STATUS_NOT_FOUND;

        } else {
            if (fn_delete_status($status_data['status'], $status_data['type'])) {
                $status = Response::STATUS_OK;
                $data['message'] = 'Ok';
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function privileges()
    {
        return array(
            'create' => 'manage_order_statuses',
            'update' => 'manage_order_statuses',
            'delete' => 'manage_order_statuses',
            'index'  => 'manage_order_statuses'
        );
    }

}
