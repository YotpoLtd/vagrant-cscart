<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

use \Tygh\Registry;

$addon_settings = Registry::get('addons.hybrid_auth');

$providers = array(
    'OpenID' => 'openid',
    'Yahoo' => 'yahoo',
    /*'AOL' => 'aol',*/
    'Google' => 'google',
    'Facebook' => 'facebook',
    'Twitter' => 'twitter',
    //'Live' => 'live',
    'MySpace' => 'myspace',
    'LinkedIn' => 'linkedin',
    //'Foursquare' => 'foursquare',
);

$config = array(
    'base_url' => fn_url('auth.process'),

    // if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
    'debug_mode' => false,

    'debug_file' => '',
);

foreach ($providers as $full_name => $provider_id) {
    $config['providers'][$full_name] = array(
        'enabled' => $addon_settings[$provider_id . '_status'] == 'Y' ? true : false
    );

    foreach ($addon_settings as $setting_name => $setting_value) {
        if (strpos($setting_name, $provider_id) !== false) {
            $provider_setting = str_replace($provider_id . '_', '', $setting_name);
            if ($provider_setting == 'status') {
                continue;
            }

            $config['providers'][$full_name]['keys'][$provider_setting] = $setting_value;
        }
    }
}

return $config;
