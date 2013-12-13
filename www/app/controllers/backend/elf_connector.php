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
use Tygh\Storage;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (Registry::get('config.demo_mode')) {
    // ElFinder should not work in demo mode
    $message = json_encode(array('error' => __('error_demo_mode')));
    exit($message);
}

include(Registry::get('config.dir.root') . '/js/lib/elfinder/connectors/php/elFinder.class.php');

$opts = array(
    'rootAlias' => __('home'),
    'tmbDir' => '',
    'dirSize' => false,
    'fileMode' => DEFAULT_FILE_PERMISSIONS,
    'dirMode' => DEFAULT_DIR_PERMISSIONS,
    'uploadDeny' => Registry::get('config.forbidden_mime_types'),
    'disabled' => array('mkfile', 'rename', 'paste', 'read', 'edit', 'archive', 'extract'),
);

if ($mode == 'files') {

    $files_path = Registry::get('config.dir.files');

    if (Registry::get('runtime.company_id')) {
        $files_path = Registry::get('config.dir.files') . Registry::get('runtime.company_id') . '/';
    }

    fn_mkdir($files_path);

    $opts['root'] = $files_path;
    $opts['URL'] = Registry::get('config.current_location') . '/';

    $fm = new \elFinder($opts);

    $fm->run();

} elseif ($mode == 'images') {

    $extra_path = '';

    if (Registry::get('runtime.company_id')) {
        $extra_path .= 'companies/' . Registry::get('runtime.company_id') . '/';
    }

    fn_mkdir(Storage::instance('images')->getAbsolutePath($extra_path));

    $opts['root'] = Storage::instance('images')->getAbsolutePath($extra_path);
    $opts['URL'] = Storage::instance('images')->getUrl($extra_path);

    $fm = new elFinder($opts);
    $fm->run();
}

exit;
