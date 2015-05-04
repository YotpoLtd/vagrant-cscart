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

class Categories extends AEntity
{
    public function index($id = 0, $params = array())
    {
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        if (!empty($id)) {
            $data = fn_get_category_data($id, $lang_code);

            if (empty($data)) {
                $status = Response::STATUS_NOT_FOUND;
            } else {
                $status = Response::STATUS_OK;
            }

        } else {
            if (!empty($params['category_ids']) && is_array($params['category_ids'])) {
                $params['item_ids'] = $params['category_ids'];
            }
            $params['plain'] = $this->safeGet($params, 'plain', true);
            $params['simple'] = $this->safeGet($params, 'simple', false);
            $params['group_by_level'] = $this->safeGet($params, 'group_by_level', false);

            list($data, ) = fn_get_categories($params, $lang_code);
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

        unset($params['category_id']);
        $category_id = fn_update_category($params, 0);

        if ($category_id) {
            $status = Response::STATUS_CREATED;
            $data = array(
                'category_id' => $category_id,
            );
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function update($id, $params)
    {
        $data = array();
        $status = Response::STATUS_BAD_REQUEST;
        unset($params['category_id']);

        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);
        $category_id = fn_update_category($params, $id, $lang_code);

        if ($category_id) {
            $status = Response::STATUS_OK;
            $data = array(
                'category_id' => $category_id
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

        if (fn_delete_category($id)) {
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
            'create' => 'manage_catalog',
            'update' => 'manage_catalog',
            'delete' => 'manage_catalog',
            'index'  => 'view_catalog'
        );
    }

    public function childEntities()
    {
        return array(
            'products'
        );
    }
}
