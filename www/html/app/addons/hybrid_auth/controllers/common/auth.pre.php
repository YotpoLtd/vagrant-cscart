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

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'login_provider') {
    // change the following paths if necessary
    $lib_path = Registry::get('config.dir.addons') . 'hybrid_auth/lib/';
    $config = $lib_path . 'config.php';

    require_once($lib_path . 'Hybrid/Auth.php');

    $view = Registry::get('view');

    try {
        $hybridauth = new Hybrid_Auth($config);
    }
    // if sometin bad happen
    catch( Exception $e ){
        $message = "";

        switch ( $e->getCode() ) {
            case 0 : $message = __('hybrid_auth_unspecified_error'); break;
            case 1 : $message = __('hybrid_auth_configuration_error'); break;
            case 2 : $message = __('hybrid_auth_provider_error_configuration'); break;
            case 3 : $message = __('hybrid_auth_wrong_provider'); break;
            case 4 : $message = __('hybrid_auth_missing_credentials'); break;
            case 5 : $message = __('hybrid_auth_failed_auth'); break;

            default: $message = __('hybrid_auth_unspecified_error');
        }

        fn_set_notification('E', __('error'), $message);
        $view->display('addons/hybrid_auth/views/auth/login_error.tpl');

        exit();
    }

    $provider  = !empty($_REQUEST['provider']) ? $_REQUEST['provider'] : '';

    if (!empty($provider) && $hybridauth->isConnectedWith($provider)) {
        $adapter = $hybridauth->getAdapter($provider);

        try {
            $auth_data = $adapter->getUserProfile();
        } catch (Exception $e) {
            fn_set_notification('E', __('error'), $e->getMessage());

            $view->display('addons/hybrid_auth/views/auth/login_error.tpl');
            exit();
        }

        $condition = db_quote('identifier = ?s', $auth_data->identifier);

        if (fn_allowed_for('ULTIMATE')) {
            if (Registry::get('settings.Stores.share_users') == 'N' && AREA != 'A') {
                $condition .= fn_get_company_condition('?:users.company_id');
            }
        }

        $user_data = db_get_row("SELECT user_id, password FROM ?:users WHERE $condition");

        if (empty($user_data['user_id'])) {
            Registry::get('settings.General.address_position') == 'billing_first' ? $address_zone = 'b' : $address_zone = 's';
            $user_data = array();
            $user_data['identifier'] = $auth_data->identifier;
            $user_data['email'] = (!empty($auth_data->verifiedEmail)) ? $auth_data->verifiedEmail : ((!empty($auth_data->email)) ? $auth_data->email : '');
            $user_data['user_login'] = (!empty($auth_data->verifiedEmail)) ? $auth_data->verifiedEmail : ((!empty($auth_data->email)) ? $auth_data->email : $auth_data->displayName);
            $user_data['user_type'] = 'C';
            $user_data['is_root'] = 'N';
            $user_data['password1'] = $user_data['password2'] = '';
            $user_data[$address_zone . '_firstname'] = (!empty($auth_data->firstName)) ? $auth_data->firstName : '';
            $user_data[$address_zone . '_lastname'] = (!empty($auth_data->lastName)) ? $auth_data->lastName : '';

            if (empty($user_data['email'])) {
                $user_data['email'] = 'noemail-' . TIME . '@example.com';
            }

            list($user_data['user_id'], $profile_id) = fn_update_user('', $user_data, $auth, true, false, false);
        }

        $user_status = (empty($user_data['user_id'])) ? LOGIN_STATUS_USER_NOT_FOUND : fn_login_user($user_data['user_id']);

        if ($user_status == LOGIN_STATUS_OK) {
            if (empty($user_data['password'])) {
                fn_set_notification('W', __('warning'), __('hybrid_auth_need_update_profile'));
                $redirect_url = 'profiles.update';
            } else {
                $redirect_url = (!empty($_REQUEST['return_url'])) ? $_REQUEST['return_url'] : fn_url();
            }

        } elseif ($user_status == LOGIN_STATUS_USER_DISABLED) {

            fn_set_notification('E', __('error'), __('error_account_disabled'));
            $redirect_url = (!empty($_REQUEST['return_url'])) ? $_REQUEST['return_url'] : fn_url();

        } elseif ($user_status == LOGIN_STATUS_USER_NOT_FOUND) {
            fn_delete_notification('user_exist');
            fn_set_notification('W', __('warning'), __('hybrid_auth_cant_create_profile'));
            $redirect_url = (!empty($_REQUEST['return_url'])) ? $_REQUEST['return_url'] : fn_url();
        }

        Registry::get('view')->assign('redirect_url', fn_url($redirect_url));
        Registry::get('view')->display('addons/hybrid_auth/views/auth/login_error.tpl');
        exit;
    }

    if (!empty( $provider )) {
        $params = array();

        if ($provider == "OpenID") {
            $params["openid_identifier"] = @ $_REQUEST["openid_identifier"];
        }
    }

    if (!empty($_REQUEST['redirect_to_idp'])) {
        try {
            $adapter = $hybridauth->authenticate($provider, $params);

        } catch (Exception $e) {
            fn_set_notification('E', __('error'), $e->getMessage());
            $view->display('addons/hybrid_auth/views/auth/login_error.tpl');

            exit();
        }

    } else {
        $view->assign('provider', $provider);
        $view->display('addons/hybrid_auth/views/auth/loading.tpl');
    }

    exit;

} elseif ($mode == 'process') {
    $lib_path = Registry::get('config.dir.addons') . 'hybrid_auth/lib/';

    require_once($lib_path . 'Hybrid/Auth.php');
    require_once($lib_path . 'Hybrid/Endpoint.php');

    Hybrid_Endpoint::process();

} elseif ($mode == 'logout') {
    // Remove Hybrid auth data
    unset($_SESSION['HA::CONFIG'], $_SESSION['HA::STORE']);
}
