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

class Users extends AEntity
{
    /**
     * Gets user data for a specified id; if no id specified, gets user list
     * satisfying filter conditions specified  in params
     *
     * @param  int   $id     User identifier
     * @param  array $params Filter params (user_ids param ignored on getting one user)
     * @return mixed
     */
    public function index($id = 0, $params = array())
    {
        if (!empty($id)) {
            $params = array();
            $params['user_id'] = $id;
        } elseif (!empty($params['user_ids']) && is_array($params['user_ids'])) {
            $params['user_id'] = $params['user_ids'];
        }

        $auth = $this->auth;

        list($data, ) = fn_get_users($params, $auth);

        if (!empty($id)) {
            $data = reset($data);
        }

        if (!empty($data) || empty($id)) {
            $status = Response::STATUS_OK;
        } else {
            $status = Response::STATUS_NOT_FOUND;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function create($params)
    {
        $auth = $this->auth;
        unset($params['user_id']);
        $user_id = "";

        list($user_id, $profile_id) = fn_update_user($user_id, $params, $auth, false, false);

        if ($user_id) {
            $status = Response::STATUS_CREATED;
            $data = array(
                'user_id' => $user_id,
                'profile_id' => $profile_id
            );
        } else {
            $data = array();
            $status = Response::STATUS_BAD_REQUEST;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function update($id, $params)
    {
        $auth = $this->auth;

        $data = array();
        $status = Response::STATUS_BAD_REQUEST;

        unset($params['user_id']);

        list($user_id, $profile_id) = fn_update_user($id, $params, $auth, false, false);
        if ($user_id) {
            $status = Response::STATUS_OK;
            $data = array(
                'user_id' => $user_id,
                'profile_id' => $profile_id
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

        if (fn_delete_user($id)) {
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
            'create' => 'manage_users',
            'update' => 'manage_users',
            'delete' => 'manage_users',
            'index'  => 'view_users'
        );
    }
}
