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

class Languages extends AEntity
{
    public function index($id = 0, $params = array())
    {
        $status = Response::STATUS_OK;
        $data = \Tygh\Languages\Languages::getAll();

        if ($data && $id) {
            foreach ($data as $lang_data) {
                if ($lang_data['lang_id'] == $id) {
                    $data = $lang_data;
                    break;
                } else {
                    $data = array();
                }
            }
        } elseif ($data && $lang_code = $this->safeGet($params, 'lang_code', '')) {
            if (!empty($data[$lang_code])) {
                $data = $data[$lang_code];
            } else {
                $status = Response::STATUS_NOT_FOUND;
                $data = array();
            }
        }

        if (!$data) {
            $status = Response::STATUS_NOT_FOUND;
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

        unset($params['lang_id']);
        $lang_id = \Tygh\Languages\Languages::update($params, 0);

        if ($lang_id) {
            $status = Response::STATUS_CREATED;
            $data = array(
                'lang_id' => $lang_id,
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
        unset($params['lang_id']);

        $lang_id = \Tygh\Languages\Languages::update($params, $id);

        if ($lang_id) {
            $status = Response::STATUS_OK;
            $data = array(
                'lang_id' => $lang_id
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
        $status = Response::STATUS_BAD_REQUEST;

        if (\Tygh\Languages\Languages::deleteLanguages(array($id))) {
            $status = Response::STATUS_OK;
            $data['message'] = 'Ok';
        } elseif (!fn_notification_exists('type', 'W')) {
            $status = Response::STATUS_NOT_FOUND;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function privileges()
    {
        return array(
            'create' => 'manage_languages',
            'update' => 'manage_languages',
            'delete' => 'manage_languages',
            'index'  => 'view_languages'
        );
    }

    public function childEntities()
    {
        return array(
            'langvars'
        );
    }
}
