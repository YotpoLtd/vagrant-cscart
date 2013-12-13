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

define('ERROR_HEADER', 'Error');

function fn_store_import_after_error($error, $debug_data)
{
    $msg = is_array($error) ? implode("\n", $error) : $error;
    fn_set_notification('E', ERROR_HEADER, $msg);
    if ($debug_data) {
        $params = array('db_host', 'db_name', 'db_user', 'db_password', 'crypt_key');
        $pattern = '/(\s*\[.*?(' . implode('|', $params) . ')[^\[\]]*\] => )[^\s]*/iS';
        $msg .= preg_replace($pattern, '$1*****', print_r($debug_data, true));
    }

    \Tygh\Logger::instance()->write($msg);
    $filename = Registry::get('config.dir.database') . 'export.sql';
    if (file_exists($filename)) {
        $new_filename = $filename . '.' . date('Y-m-d_H-i') . '.sql';
        fn_rename($filename, $new_filename);
    }
}

function fn_store_import_after_set_progress($prop, $value, $extra)
{
    \Tygh\Logger::instance()->write($value);
}
