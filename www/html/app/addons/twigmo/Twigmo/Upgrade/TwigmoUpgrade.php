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

namespace Twigmo\Upgrade;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

use Tygh\Registry;
use Tygh\Settings;
use Tygh\Http;
use Tygh\BlockManager\Exim;
use Tygh\BlockManager\Location;
use Twigmo\Core\Functions\Lang;
use Twigmo\Core\Functions\Image\TwigmoImage;
use Twigmo\Upgrade\TwigmoUpgradeMethods;
use Twigmo\Core\Functions\UserAgent;

class TwigmoUpgrade
{
    final public static function checkUpgradePermissions($upgrade_dirs, $is_writable = true)
    {
        foreach ($upgrade_dirs as $upgrade_dir) {
            if (is_array($upgrade_dir)) {
                $is_writable = self::checkUpgradePermissions(
                    $upgrade_dir,
                    $is_writable
                );
            } else {
                $check_result = array();
                fn_uc_check_files($upgrade_dir, array(), $check_result, '', '');
                $is_writable = empty($check_result);
            }
            if (!$is_writable) {
                break;
            }
        }

        return $is_writable;
    }

    final public static function updateTwigmoOptions($options, $company_id = null)
    {
        $logoURL = Registry::get('addons.twigmo.logoURL');
        $faviconURL = Registry::get('addons.twigmo.faviconURL');
        $options['logoURL'] =
            empty($options['logoURL']) ?
            (!empty($logoURL) ? $logoURL : TwigmoImage::getDefaultLogoUrl()) :
            $options['logoURL'];
        $options['faviconURL'] =
            empty($options['faviconURL']) ?
            (!empty($faviconURL) ? $faviconURL : TwigmoImage::getFaviconUrl()) :
            $options['faviconURL'];

        if (!empty($options['version_forced'])) {
            $options['version'] = $options['version_forced'];
            unset($options['version_forced']);
        } elseif (self::addonIsUpdated()) {
            $options['version'] = TWIGMO_VERSION;
        }
        $logos = fn_filter_uploaded_data('tw_settings');
        $logo_names = array('logo', 'favicon');
        foreach ($logo_names as $logo_name) {
            if ($logos and !empty($logos[$logo_name])) {
                $logo = $logos[$logo_name];
                $filename = TwigmoImage::getImagesPath() . $logo['name'];
                @touch($filename);
                if (!fn_copy($logo['path'], $filename)) {
                    $_text = __('text_cannot_create_file');
                    $text = str_replace('[file]', $filename, $_text);
                    fn_set_notification('E', __('error'), $text);
                } else {
                    $options[$logo_name . 'URL'] = $filename;
                }
                @unlink($logo['path']);
            }
        }

        if (
            !$section = Settings::instance()->getSectionByName(
                'twigmo',
                Settings::ADDON_SECTION
            )
        ) {
            $section = Settings::instance()->updateSection(array(
                'parent_id' =>      0,
                'edition_type' =>   'ROOT,ULT:VENDOR',
                'name' =>           'twigmo',
                'type' =>           Settings::ADDON_SECTION
            ));
        }

        foreach ($options as $option_name => $option_value) {
            if ($option_name == 'twigmo_license') {
                continue;
            }
            if (
                !$setting_id = Settings::instance()->getId(
                    $option_name,
                    'twigmo'
                )
            ) {
                $setting_id = Settings::instance()->update(array(
                    'name' =>           $option_name,
                    'section_id' =>     $section['section_id'],
                    'edition_type' =>   'ROOT,ULT:VENDOR',
                    'section_tab_id' => 0,
                    'type' =>           'A',
                    'position' =>       0,
                    'is_global' =>      'N',
                    'handler' =>        ''
                ), null, null, true);
            }
            Settings::instance()->updateValueById(
                $setting_id,
                $option_value,
                $company_id ? $company_id : null
            );
        }

        return;
    }

    final public static function connectToTwigmo($email, $password, $user_id)
    {
        $user_name = fn_get_user_name($user_id);
        $_user_data = explode(' ', $user_name);
        $user_data['firstname'] = $_user_data[0];
        $user_data['lastname'] = $_user_data[1];
        $params = array (
            'dispatch' => 'connect_store.connect',
            'email' => $email,
            'store_name' => Registry::get('config.http_host')
                . Registry::get('config.http_path'),
            'admin_url' => fn_url(
                'twigmo.post',
                'A',
                Registry::get('settings.General.secure_admin') == 'Y' ?
                    'https' :
                    'http'
            ),
            'customer_url' => fn_url(
                'twigmo.post',
                'C',
                fn_twg_use_https_for_customer() ?
                    'https' :
                    'http'
            ),
            'firstname' => $user_data['firstname'],
            'lastname' => $user_data['lastname'],
            'password' => md5($password),
            'addon_version' => TWIGMO_VERSION
        );
        $twigmo = fn_twg_init_twigmo();
        if (
            $twigmo->sendRequest($params, 'GET')
            && !empty($twigmo->response_data['access_id'])
        ) {
            // store connected update connection data
            $addon_options = array();
            $addon_options['access_id'] = $twigmo->response_data['access_id'];
            $addon_options['secret_access_key'] = $twigmo->response_data['secret_access_key'];
            $addon_options['email'] = $email;
            $addon_options['password'] = $password;
            self::updateTwigmoOptions($addon_options);
            Registry::set('addons.twigmo.access_id', $twigmo->response_data['access_id']);
            Registry::set(
                'addons.twigmo.secret_access_key',
                $twigmo->response_data['secret_access_key']
            );
            Registry::set('addons.twigmo.email', $email);

        } else {
            if (!empty($twigmo->errors)) {
                fn_set_notification('E', __('error'), implode('<br />', $twigmo->errors));
            } else {
                fn_set_notification(
                    'E',
                    __('error'),
                    __('twgadmin_error_cannot_connect_store')
                );
            }
        }

        return $twigmo;
    }

    final public static function getUpgradeDirs($install_src_dir)
    {
        $dirs = array();
        $addon_path = 'twigmo/';
        $full_addon_path = 'addons/twigmo/';
        $repo_path = 'var/themes_repository/basic/';
        $backup_files_path = 'backup_files/';
        $file_areas = array(
            'media' => 'media/images',
            'css' => 'css',
            'templates' => 'templates'
        );

        // Installed dirs
        $dirs['installed'] = array(
            'addon' => Registry::get('config.dir.addons') . $addon_path,
        );
        foreach ($file_areas as $key => $file_area) {
            $dirs['installed'][$key . '_backend'] = fn_get_theme_path(
                '[themes]/[theme]/',
                'A'
            ) . $file_area . '/' . $full_addon_path;
            $dirs['installed'][$key . '_frontend'][0] = fn_get_theme_path(
                '[themes]/[theme]/',
                'C'
            ) . $file_area . '/' . $full_addon_path;
        }

        // Repo dirs
        $dirs['repo'] = array(
            'addon' => Registry::get('config.dir.addons') . $addon_path,
         );
        foreach ($file_areas as $key => $file_area) {
            $dirs['repo'][$key . '_backend'] = fn_get_theme_path(
                '[themes]/',
                'A'
            ) . $file_area . '/' . $full_addon_path;
            $dirs['repo'][$key . '_frontend'] = fn_get_theme_path(
                '[repo]/[theme]/',
                'C'
            ) . $file_area . '/' . $full_addon_path;
        }

        // Distr dirs
        $dirs['distr'] = array(
            'addon' => $install_src_dir . 'app/' . $full_addon_path,
        );
        foreach ($file_areas as $key => $file_area) {
            $dirs['distr'][$key . '_backend'] = $install_src_dir . 'design/' . 'backend/' . $file_area . '/' . $full_addon_path;
            $dirs['distr'][$key . '_frontend'] = $install_src_dir . $repo_path . $file_area . '/' . $full_addon_path;
        }

        // Backup dirs
        $dirs['backup_root'] = TwigmoUpgradeMethods::getBackupDir();
        $dirs['backup_files'] = array(
            'addon' => $dirs['backup_root']
             . $backup_files_path
             . 'app/'
             . $full_addon_path,
        );
        foreach ($file_areas as $key => $file_area) {
            $dirs['backup_files'][$key . '_backend'] =
                $dirs['backup_root']
                . $backup_files_path
                . fn_get_theme_path(
                   '[relative]/[theme]/',
                   'A'
                )
                . $file_area . '/'
                . $full_addon_path;
            $dirs['backup_files'][$key . '_frontend'][0] =
                $dirs['backup_root']
                . $backup_files_path
                . fn_get_theme_path(
                   '[relative]/[theme]/',
                   'C'
                  )
                . $file_area . '/'
                . $full_addon_path;
        }

        // Settings backup dirs
        $dirs['backup_settings'] = $dirs['backup_root'] . 'backup_settings/';
        $dirs['backup_company_settings'] = array($dirs['backup_settings'] . 'companies/0/');
        if (fn_allowed_for('ULTIMATE')) {
            $company_ids = fn_get_all_companies_ids();
            $dirs['backup_company_settings'] = array();
            foreach ($file_areas as $key => $file_area) {
                $dirs['backup_files'][$key . '_frontend'] =
                $dirs['installed'][$key . '_frontend'] = array();
            }
            foreach ($company_ids as $company_id) {
                $dirs['backup_company_settings'][$company_id] =
                 $dirs['backup_settings'] . 'companies/' . $company_id . '/';

                // Installed frontend
                foreach ($file_areas as $key => $file_area) {
                    $dirs['installed'][$key . '_frontend'][$company_id] = fn_get_theme_path(
                        '[themes]/[theme]/',
                        'C',
                        $company_id
                    ) . $file_area . '/' . $full_addon_path;
                }

                // Backup frontend
                foreach ($file_areas as $key => $file_area) {
                    $dirs['backup_files'][$key . '_frontend'][$company_id] =
                        $dirs['backup_root']
                        . $backup_files_path
                        . fn_get_theme_path(
                           '[relative]/[theme]/',
                           'C',
                           $company_id
                          )
                        . $file_area . '/'
                        . $full_addon_path;
                }
            }
        }

        return $dirs;
    }

    final public static function getNextVersionInfo()
    {
        $version_info = fn_get_contents(TWIGMO_UPGRADE_DIR . TWIGMO_UPGRADE_VERSION_FILE);

        return $version_info ?
            unserialize($version_info) :
            array(
                'latest_version' => '',
                'next_version' => '',
                'description' => ''
            );
    }

    final public static function downloadDistr()
    {
        // Get needed version
        $_version = self::GetNextVersionInfo();
        $version = $_version['next_version'];
        if (!$version) {
            return false;
        }
        $download_file_name = 'twigmo.cs' . preg_replace(
            '/\./',
            '',
            preg_replace('/\.[0-9]+$/', 'x', PRODUCT_VERSION)
        ) . '-' . $version . '.tgz';
        $download_file_dir = TWIGMO_UPGRADE_DIR . $version . '/';
        $download_file_path = $download_file_dir . $download_file_name;
        $unpack_path = $download_file_path . '_unpacked';
        fn_rm($download_file_dir);
        fn_mkdir($download_file_dir);
        fn_mkdir($unpack_path);

        $data = fn_get_contents(TWIGMO_UPGRADE_SERVER . $download_file_name);
        if (!fn_is_empty($data)) {
            fn_put_contents($download_file_path, $data);
            $res = fn_decompress_files($download_file_path, $unpack_path);

            if (!$res) {
                fn_set_notification(
                    'E',
                    __('error'),
                    __('text_uc_failed_to decompress_files')
                );

                return false;
            }

            return $unpack_path . '/';
        } else {
            fn_set_notification(
                'E',
                __('error'),
                __('text_uc_cant_download_package')
            );

            return false;
        }
    }

    final public static function copyFiles($source, $dest)
    {
        if (is_array($source)) {
            foreach ($source as $key => $src) {
                self::copyFiles($src, $dest[$key]);
            }
        } else {
            fn_uc_copy_files($source, $dest);
        }

        return;
    }

    final public static function execUpgradeFunc($install_src_dir, $file_name)
    {
        $file = $install_src_dir . '/addons/twigmo/' . $file_name . '.php';
        if (file_exists($file)) {
            require_once($file);
        }

        return;
    }

    final public static function backupSettings($upgrade_dirs)
    {
        // Backup addon's settings
        $schema = fn_get_schema('upgrade', 'twigmo_settings', 'php', false);
        $current_settings = Registry::get('addons.twigmo');
        //$schema = fn_get_schema('upgrade', 'twigmo_settings', 'php', false);
        fn_twg_write_to_file(
            $upgrade_dirs['backup_settings'] . 'settings_all.bak',
            $current_settings
        );
        foreach ($upgrade_dirs['backup_company_settings'] as $company_id => $dir) {
            $saved_settings = array();
            if ($company_id) {
                // Get settings for certain company
                $section = Settings::instance()->getSectionByName(
                    'twigmo',
                    Settings::ADDON_SECTION
                );
                $settings = Settings::instance()->getList(
                    $section['section_id'],
                    0,
                    false,
                    $company_id
                );
                if (!empty($settings['main'])) {
                    foreach ($settings['main'] as $setting) {
                        if (in_array($setting['name'], $schema['saved_settings'])) {
                            $saved_settings[$setting['name']] = $setting['value'];
                        }
                    }
                }
            } else {
                foreach ($schema['saved_settings'] as $setting) {
                    if (isset($current_settings[$setting])) {
                        $saved_settings[$setting] = $current_settings[$setting];
                    }
                }
            }
            fn_twg_write_to_file($dir . 'settings.bak', $saved_settings);
        }

        // Backup twigmo blocks
        foreach ($upgrade_dirs['backup_company_settings'] as $company_id => $dir) {
            $location = Location::instance($company_id)->get('twigmo.post');
            if ($location) {
                $content = Exim::instance($company_id)->export(
                    array($location['location_id']),
                    array()
                );
                if ($content) {
                    fn_twg_write_to_file($dir . '/blocks.xml', $content, false);
                }
            }
        }

        // Backup twigmo langvars
        $languages = Lang::getLanguages();
        foreach ($languages as $language) {
            // Prepare langvars for backup
            $langvars = Lang::getAllLangVars($language['lang_code']);
            $langvars_formated = array();
            foreach ($langvars as $name => $value) {
                $langvars_formated[] = array('name' => $name, 'value' => $value);
            }
            fn_twg_write_to_file(
                $upgrade_dirs['backup_settings'] . '/lang_' . $language['lang_code'] . '.bak',
                $langvars_formated
            );
        }
        if (fn_allowed_for('ULTIMATE')) {
            db_export_to_file(
                $upgrade_dirs['backup_settings'] . 'lang_ult.sql',
                array(db_quote('?:ult_language_values')),
                'Y',
                'Y',
                false,
                false,
                false
            );
        }

        return true;
    }

    final public static function restoreSettingsAndCSS($upgrade_dirs, $user_id)
    {
        // Restore langvars - for all languages except EN and RU
        $languages = Lang::getLanguages();
        $except_langs = array('en', 'ru');
        foreach ($languages as $language) {
            $backup_file =
                $upgrade_dirs['backup_settings']
                . 'lang_' . $language['lang_code'] . '.bak';
            if (
                !in_array(
                    $language['lang_code'],
                    $except_langs
                )
                and file_exists($backup_file)
            ) {
                fn_update_lang_var(
                    unserialize(
                        fn_get_contents($backup_file)
                    ),
                    $language['lang_code']
                );
            }
        }

        // Restore blocks
        foreach ($upgrade_dirs['backup_company_settings'] as $company_id => $dir) {
            $backup_file = $dir . 'blocks.xml';
            if (file_exists($backup_file)) {
                Exim::instance($company_id)->importFromFile($backup_file);
            }
        }

        // Restore settings if addon was connected
        $all_settings = unserialize(
            fn_get_contents($upgrade_dirs['backup_settings'] . 'settings_all.bak')
        );
        if (!empty($all_settings['access_id'])) {
            // Connect addon
            self::connectToTwigmo($all_settings['email'], $all_settings['password'], $user_id);
        }
        // Restore companys settings
        foreach ($upgrade_dirs['backup_company_settings'] as $company_id => $dir) {
            $company_settings = unserialize(fn_get_contents($dir . 'settings.bak'));
            $company_settings['version_forced'] = TWIGMO_VERSION;
            self::updateTwigmoOptions($company_settings, $company_id);
            fn_clear_cache();
        }
        // Restore custom.css files
        foreach ($upgrade_dirs['backup_files']['css_frontend'] as $dir) {
            $css_files = fn_get_dir_contents($dir, false, true, 'css');
            foreach ($css_files as $file_name) {
                foreach ($upgrade_dirs['backup_files']['css_frontend'] as $company_id => $dir) {
                    if (is_file($dir . $file_name)) {
                        fn_uc_copy_files(
                            $dir . $file_name,
                            $upgrade_dirs['installed']['css_frontend'][$company_id] . $file_name
                        );
                    }
                }
            }
        }

        return;
    }

    final public static function updateFiles($upgrade_dirs)
    {
        // Remove all addon's files
        foreach ($upgrade_dirs['repo'] as $dir) {
            TwigmoUpgradeMethods::removeDirectoryContent($dir);
        }
        // Copy files from distr to repo
        self::copyFiles($upgrade_dirs['distr'], $upgrade_dirs['repo']);

        return;
    }

    // Check if twigmo addon was reinstalled after uploading new files
    final public static function addonIsUpdated()
    {
        $settings = Registry::get('addons.twigmo');

        return (is_array($settings) and !empty($settings['settings']) and $settings['settings'] == 'settings.tpl'
            and (empty($settings['version']) or $settings['version'] == TWIGMO_VERSION)
        );
    }

    final public static function checkForUpgrade()
    {
        UserAgent::updateUaRules();
        if (
            !empty($_SESSION['auth'])
            && $_SESSION['auth']['area'] == 'A'
            && !empty($_SESSION['auth']['user_id'])
            && fn_check_user_access($_SESSION['auth']['user_id'], 'upgrade_store')
        ) {
            UserAgent::sendUaStat();
            $params = array('store_version' => PRODUCT_VERSION, 'twigmo_version' => TWIGMO_VERSION);
            $version_info_json = Http::get(
                TWG_CHECK_UPDATES_SCRIPT,
                $params
            );
            $version_info = json_decode($version_info_json, true);

            if ($version_info['next_version'] and $version_info['next_version'] != TWIGMO_VERSION) {
                $msg = str_replace(
                    '[link]',
                    fn_url('addons.update&addon=twigmo&selected_section=twigmo_addon'),
                    __('twgadmin_text_updates_available')
                );
                fn_set_notification('W', __('notice'), $msg, 'S', 'twigmo_upgrade');
                // Save version info to file
                fn_twg_save_version_info($version_info);
                return true;
            }
        }
        return false;
    }
}
