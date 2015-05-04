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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

use Tygh\Registry;
use Tygh\BlockManager\Location;
use Twigmo\Upgrade\TwigmoUpgrade;
use Twigmo\Core\Functions\Image\TwigmoImage;
use Twigmo\Twigmo;

if (!empty($_REQUEST['addon']) && $_REQUEST['addon'] == 'twigmo' && $mode != 'uninstall') {
    $twigmo_requirements_errors = Twigmo::checkRequirements();
    if (!empty($twigmo_requirements_errors)) {
        foreach ($twigmo_requirements_errors as $error) {
            fn_set_notification('W', fn_get_lang_var('notice'),  $error);
        }
        return;
    }
}

$view = Registry::get('view');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'tw_connect') {

        fn_clear_cache();
        fn_rm(Registry::get('config.dir.cache_templates'));

        if (Registry::get('addons.twigmo.access_id')) {
            fn_set_notification(
                'E',
                __('error'),
                __('access_denied')
            );

            return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
        }

        $tw_register = $_REQUEST['tw_register'];
        if ($tw_register['password1'] != $tw_register['password2']) {
            fn_set_notification(
                'E',
                __('error'),
                __('error_passwords_dont_match')
            );

            return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
        }

        if (empty($tw_register['accept_terms'])) {
            fn_set_notification(
                'E',
                __('error'),
                __('checkout_terms_n_conditions_alert')
            );

            return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
        }

        // get license text
        $license = Twigmo::getLicenseText(Registry::get('addons.twigmo.license'));
        if (!empty($license)) {
            Registry::set('addons.twigmo.license', $license);
            Registry::get('view')->assign('twigmo_license', $license);
        }

        $twigmo = TwigmoUpgrade::connectToTwigmo(
            DEFAULT_ADMIN_EMAIL == $tw_register['email']?
                'special':
                $tw_register['email'],
            $tw_register['password1'],
            $auth['user_id']
        );

        if (!empty($twigmo->response_data['access_id'])) {

            // Save default settings
            $settings = array(
                'use_mobile_frontend' => 'both_tablet_and_phone',
                'home_page_content' => 'home_page_blocks',
                'selected_skin' => 'default',
                'only_req_profile_fields' => 'N',
                'url_for_facebook' => '',
                'url_for_twitter' => '',
                'faviconURL' => '',
                'logoURL' => ''
            );
            TwigmoUpgrade::updateTwigmoOptions($settings);

            fn_set_notification(
                'N',
                __('notice'),
                __('twgadmin_text_store_connected')
            );
        }

        if (defined('AJAX_REQUEST')) {
            $tw_register['version'] = TWIGMO_VERSION;
            $view->assign('tw_register', $tw_register);
            $view->assign('default_logo', TwigmoImage::getDefaultLogoUrl());
            $view->assign('favicon', TwigmoImage::getFaviconUrl());
            $view->assign('next_version_info', TwigmoUpgrade::getNextVersionInfo());
            $view->assign('locations_info', fn_twg_get_locations_info());
            $view->display('addons/twigmo/hooks/index/styles.post.tpl');
            $view->display('addons/twigmo/settings/connect.tpl');
            $view->display('addons/twigmo/settings/settings.tpl');
            exit;
        }

        return array(CONTROLLER_STATUS_REDIRECT, 'addons.update&addon=twigmo');
    }

    if ($mode == 'update' && $_REQUEST['addon'] == 'twigmo'
        && !empty($_REQUEST['tw_settings'])
    ) {
        $company_id = Registry::get('runtime.company_id');
        TwigmoUpgrade::updateTwigmoOptions($_REQUEST['tw_settings'], $company_id);
        fn_clear_cache();
    }
} elseif ($mode == 'update') {
    if ($_REQUEST['addon'] == 'twigmo') {
        fn_clear_cache();
        if (!empty($_REQUEST['selected_section'])
            and $_REQUEST['selected_section'] == 'twigmo_addon') {
            fn_delete_notification('twigmo_upgrade');
        }

        // get license text
        $license = Twigmo::getLicenseText(Registry::get('addons.twigmo.license'));
        if (!empty($license)) {
            Registry::set('addons.twigmo.license', $license);
            Registry::get('view')->assign('twigmo_license', $license);
        }

        if (!TwigmoUpgrade::addonIsUpdated()) {
            fn_set_notification(
                'W',
                __('notice'),
                __('twgadmin_reinstall')
            );
        }

        $view = Registry::get('view');
        $view->assign('default_logo', TwigmoImage::getDefaultLogoUrl());
        $view->assign('favicon', TwigmoImage::getFaviconUrl());
        $tw_register['version'] = TWIGMO_VERSION;
        $view->assign('tw_register', $tw_register);
        $view->assign('next_version_info', TwigmoUpgrade::getNextVersionInfo());
    }
}
