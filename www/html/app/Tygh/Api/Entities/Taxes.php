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

class Taxes extends AEntity
{

    public function index($id = 0, $params = array())
    {
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        if (!empty($id)) {
            $data = fn_get_tax($id, $lang_code);

            if (empty($data)) {
                $status = Response::STATUS_NOT_FOUND;
            } else {
                $status = Response::STATUS_OK;
            }

        } else {
            $data = fn_get_taxes($lang_code);
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
        $valid_params = true;

        if (empty($params['tax'])) {
            $data['message'] = __('api_taxes_need_tax');
            $valid_params = false;
        }

        if ($valid_params) {
            $tax_id = fn_update_tax($params, 0);

            if ($tax_id) {
                $status = Response::STATUS_CREATED;
                $data = array(
                    'tax_id' => $tax_id,
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

        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);
        $tax_id = fn_update_tax($params, $id, $lang_code);

        if ($tax_id) {
            $status = Response::STATUS_OK;
            $data = array(
                'tax_id' => $tax_id
            );
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function delete($id)
    {
        $data = array();
        $status = Response::STATUS_NOT_FOUND;

        if (fn_delete_tax($id)) {
            $status = Response::STATUS_OK;
            $data['message'] = 'Ok';
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function privileges()
    {
        return array(
            'create' => 'manage_taxes',
            'update' => 'manage_taxes',
            'delete' => 'manage_taxes',
            'index'  => 'view_taxes'
        );
    }

}
