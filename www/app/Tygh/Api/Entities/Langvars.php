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
use Tygh\Languages\Values as LanguageValues;

class Langvars extends AEntity
{
    protected function getParentLanguageCode()
    {
        $result = CART_LANGUAGE;

        if ($this->getParentName() == 'languages') {
            $parent_language = $this->getParentData();
            $result = $parent_language['lang_code'];
        }

        return $result;
    }

    public function index($id = '', $params = array())
    {
        $status = Response::STATUS_OK;
        $data = array();

        if ($id) {
            $lang_code = $this->getParentLanguageCode();
            $data = array(
                'lang_code' => $lang_code,
                'name' => $id,
                'value' => LanguageValues::getLangVar($id, $lang_code),
            );
        } else {
            $lang_code = $this->getParentLanguageCode();

            if (!$lang_code) {
                $lang_code = DESCR_SL;
            }
            $data = LanguageValues::getVariables($params, $this->safeGet($params, 'limit', 0), $lang_code);

            if ($data) {
                $data = $data[0];
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

        /**
         * Lang code is required.
         */
        if ($lang_code = $this->getParentLanguageCode()) {
            $res = LanguageValues::updateLangVar(array($params), $lang_code);

            if ($res) {
                $status = Response::STATUS_CREATED;
                $data = array(
                    'name' => $params['name'],
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
        $params['name'] = $id;

        /**
         * Lang code is required.
         */
        if ($lang_code = $this->getParentLanguageCode()) {
            $res = LanguageValues::updateLangVar(array($params), $lang_code);

            if ($res) {
                $status = Response::STATUS_OK;
                $data = array(
                    'name' => $params['name'],
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
        $status = Response::STATUS_NOT_FOUND;

        $res = LanguageValues::deleteVariables(array($id));

        if ($res) {
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
            'create' => 'manage_languages',
            'update' => 'manage_languages',
            'delete' => 'manage_languages',
            'index'  => 'view_languages'
        );
    }

    public function childEntities()
    {
        return array(
            'products'
        );
    }

    public function isValidIdentifier($id)
    {
        return preg_match('/^[0-9a-z_\-]+$/', $id);
    }
}
