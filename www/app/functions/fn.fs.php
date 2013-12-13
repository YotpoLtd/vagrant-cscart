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

use Tygh\Bootstrap;
use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Normalize path: remove "../", "./" and duplicated slashes
 *
 * @param string $path
 * @param string $separator
 * @return string normilized path
 */
function fn_normalize_path($path, $separator = '/')
{

    $result = array();
    $path = preg_replace("/[\\\\\/]+/S", $separator, $path);
    $path_array = explode($separator, $path);
    if (!$path_array[0]) {
        $result[] = '';
    }

    foreach ($path_array as $key => $dir) {
        if ($dir == '..') {
            if (end($result) == '..') {
               $result[] = '..';
            } elseif (!array_pop($result)) {
               $result[] = '..';
            }
        } elseif ($dir != '' && $dir != '.') {
            $result[] = $dir;
        }
    }

    if (!end($path_array)) {
        $result[] = '';
    }

    return fn_is_empty($result) ? '' : implode($separator, $result);
}

/**
 * Create directory wrapper. Allows to create included directories
 *
 * @param string $dir
 * @param int $perms permission for new directory
 * @return array List of directories
 */
function fn_mkdir($dir, $perms = DEFAULT_DIR_PERMISSIONS)
{
    $result = false;

    if (!empty($dir)) {

        clearstatcache();
        if (@is_dir($dir)) {

            $result = true;

        } else {

            // Truncate the full path to related to avoid problems with some buggy hostings
            if (strpos($dir, DIR_ROOT) === 0) {
                $dir = './' . substr($dir, strlen(DIR_ROOT) + 1);
                $old_dir = getcwd();
                chdir(DIR_ROOT);
            }

            $dir = fn_normalize_path($dir, '/');
            $path = '';
            $dir_arr = array();
            if (strstr($dir, '/')) {
                $dir_arr = explode('/', $dir);
            } else {
                $dir_arr[] = $dir;
            }

            foreach ($dir_arr as $k => $v) {
                $path .= (empty($k) ? '' : '/') . $v;
                clearstatcache();
                if (!is_dir($path)) {
                    umask(0);
                    $result = @mkdir($path, $perms);
                    if (!$result) {
                        $parent_dir = dirname($path);
                        $parent_perms = fileperms($parent_dir);
                        @chmod($parent_dir, 0777);
                        $result = @mkdir($path, $perms);
                        @chmod($parent_dir, $parent_perms);
                        if (!$result) {
                            break;
                        }
                    }
                }
            }

            if (!empty($old_dir)) {
                @chdir($old_dir);
            }
        }
    }

    return $result;
}

/**
 * Compress files with Tar archiver
 *
 * @param string $archive_name - name of the compressed file will be created
 * @param string $file_list - list of files to place into archive
 * @param string $dirname - directory, where the files should be get from
 * @return bool true
 */
function fn_compress_files($archive_name, $file_list, $dirname = '')
{
    $tar = new Archive_Tar($archive_name, 'gz');

    if (!is_object($tar)) {
        fn_error('Archiver initialization error');
    }

    if (!empty($dirname) && is_dir($dirname)) {
        chdir($dirname);
        $tar->create($file_list);
        chdir(Registry::get('config.dir.root'));
    } else {
        $tar->create($file_list);
    }

    return true;
}

/**
 * Extract files with Tar archiver
 *
 * @param $archive_name - name of the compressed file will be created
 * @param $file_list - list of files to place into archive
 * @param $dirname - directory, where the files should be extracted to
 * @return bool true
 */
function fn_decompress_files($archive_name, $dirname = '')
{
    $tar = new Archive_Tar($archive_name, 'gz');

    if (!is_object($tar)) {
        fn_error('Archiver initialization error');
    }

    if (!empty($dirname) && is_dir($dirname)) {
        chdir($dirname);
        $tar->extract('');
        chdir(Registry::get('config.dir.root'));
    } else {
        $tar->extract('');
    }

    return true;
}

/**
 * Get MIME type by the file name
 *
 * @param string $filename
 * @param string $not_available_result MIME type that will be returned in case all checks fail
 * @return string $file_type MIME type of the given file.
 */
function fn_get_file_type($filename, $not_available_result = 'application/octet-stream')
{
    $file_type = $not_available_result;

    static $types = array (
        'zip' => 'application/zip',
        'tgz' => 'application/tgz',
        'rar' => 'application/rar',

        'exe' => 'application/exe',
        'com' => 'application/com',
        'bat' => 'application/bat',

        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/x-icon',
        'swf' => 'application/x-shockwave-flash',

        'csv' => 'text/csv',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pdf' => 'application/pdf',

        'css' => 'text/css',
        'js' => 'text/javascript'
    );

    $ext = fn_get_file_ext($filename);

    if (!empty($types[$ext])) {
        $file_type = $types[$ext];
    }

    return $file_type;
}

/**
 * Function tries to get MIME type by different ways.
 *
 * @param string $filename Full path with name to file
 * @param boolean $check_by_extension Try to get MIME type by extension of the file
 * @param string $not_available_result MIME type that will be returned in case all checks fail
 * @return string MIME type of the given file.
 */
function fn_get_mime_content_type($filename, $check_by_extension = true, $not_available_result = 'application/octet-stream')
{
    $type = '';

    if (class_exists('finfo')) {
        $finfo_handler = @finfo_open(FILEINFO_MIME);
        if ($finfo_handler !== false) {
            $type = @finfo_file($finfo_handler, $filename);
            list($type) = explode(';', $type);
            @finfo_close($finfo_handler);
        }
    }

    if (empty($type) && function_exists('mime_content_type')) {
        $type = @mime_content_type($filename);
    }

    if (empty($type) && $check_by_extension && strpos(fn_basename($filename), '.') !== false) {
        $type = fn_get_file_type(fn_basename($filename), $not_available_result);
    }

    return !empty($type) ? $type : $not_available_result;
}

/**
 * Get the EDP downloaded
 *
 * @param string $path path to the file
 * @param string $filename file name to be displayed in download dialog
 * @return bool Always false
 */
function fn_get_file($filepath, $filename = '')
{
    $fd = @fopen($filepath, 'rb');
    if ($fd) {
        $fsize = filesize($filepath);
        $ftime = date('D, d M Y H:i:s T', filemtime($filepath)); // get last modified time

        if (isset($_SERVER['HTTP_RANGE'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 206 Partial Content');
            $range = $_SERVER['HTTP_RANGE'];
            $range = str_replace('bytes=', '', $range);
            list($range, $end) = explode('-', $range);

            if (!empty($range)) {
                fseek($fd, $range);
            }
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
            $range = 0;
        }

        if (empty($filename)) {
            // Non-ASCII filenames containing spaces and underscore characters are chunked if no locale is provided
            setlocale(LC_ALL, 'en_US.UTF8');
            $filename = fn_basename($filepath);
        }

        // Browser bug workaround: Filenames can't be sent to IE if there is any kind of traffic compression enabled on the server side
        if (USER_AGENT == 'ie') {
            if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', '1');
            }

            ini_set("zlib.output_compression", "Off");

            // Browser bug workaround: During the file download with IE, non-ASCII filenames appears with a broken encoding
            $filename = rawurlencode($filename);
        }

        header("Content-disposition: attachment; filename=\"$filename\"");
        header('Content-type: ' . fn_get_mime_content_type($filepath));
        header('Last-Modified: ' . $ftime);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . ($fsize - $range));
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);

        if ($range) {
            header("Content-Range: bytes $range-" . ($fsize - 1) . '/' . $fsize);
        }

        $result = fpassthru($fd);
        if ($result == false) {
            fclose($fd);

            return false;
        } else {
            fclose($fd);
            exit;
        }
    }

    return false;
}

/**
 * Create temporary file for uploaded file
 *
 * @param $val file path
 * @return array $val
 */
function fn_get_server_data($val)
{
    if (defined('IS_WINDOWS')) {
        $val = str_replace('\\', '/', $val);
    }
    if (strpos($val, Registry::get('config.dir.root')) === 0) {
        $val = substr_replace($val, '', 0, strlen(Registry::get('config.dir.root')));
    }

    setlocale(LC_ALL, 'en_US.UTF8');
    $path = fn_normalize_path(Registry::get('config.dir.root') . '/' . $val);
    $result = false;
    if (file_exists($path)) {

        $result = array(
            'name' => fn_basename($val),
            'path' => $path
        );

        $tempfile = fn_create_temp_file();
        fn_copy($result['path'], $tempfile);
        $result['path'] = $tempfile;
        $result['size'] = filesize($result['path']);

        $cache = Registry::get('temp_fs_data');

        if (!isset($cache[$result['path']])) { // cache file to allow multiple usage
            $cache[$result['path']] = $tempfile;
            Registry::set('temp_fs_data', $cache);
        }
    }

    return $result;
}

/**
 * Rebuilds $_FILES array to more user-friendly look
 *
 * @param string $name Name of file parameter
 * @return array Rebuilt file array
 */
function fn_rebuild_files($name)
{
    $rebuilt = array();

    if (!is_array(@$_FILES[$name])) {
        return $rebuilt;
    }

    if (isset($_FILES[$name]['error'])) {
        if (!is_array($_FILES[$name]['error'])) {
            return $_FILES[$name];
        }
    } elseif (fn_is_empty($_FILES[$name]['size'])) {
        return $_FILES[$name];
    }

    foreach ($_FILES[$name] as $k => $v) {
        if ($k == 'tmp_name') {
            $k = 'path';
        }
        $rebuilt = fn_array_multimerge($rebuilt, $v, $k);
    }

    return $rebuilt;
}

/**
 * Recursively copy directory (or just a file)
 *
 * @param string $source
 * @param string $dest
 * @param bool $silent
 * @param array $exclude_files
 * @return bool True on success, false otherwise
 */
function fn_copy($source, $dest, $silent = true, $exclude_files = array())
{
    // Simple copy for a file
    if (is_file($source)) {
        $source_file_name = fn_basename($source);
        if (in_array($source_file_name, $exclude_files)) {
            return true;
        }
        if (@is_dir($dest)) {
            $dest .= '/' . $source_file_name;
        }
        if (filesize($source) == 0) {
            $fd = fopen($dest, 'w');
            fclose($fd);
            $res = true;
        } else {
            $res = @copy($source, $dest);
        }
        @chmod($dest, DEFAULT_FILE_PERMISSIONS);
        clearstatcache(true, $dest);

        return $res;
    }

    // Make destination directory
    if ($silent == false) {
        $_dir = strpos($dest, Registry::get('config.dir.root')) === 0 ? str_replace(Registry::get('config.dir.root') . '/', '', $dest) : $dest;
        fn_set_progress('echo', $_dir);
    }

    if (!fn_mkdir($dest)) {
        return false;
    }

    // Loop through the folder
    if (@is_dir($source)) {
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            if ($dest !== $source . '/' . $entry) {
                if (fn_copy($source . '/' . $entry, $dest . '/' . $entry, $silent, $exclude_files) == false) {
                    return false;
                }
            }
        }

        // Clean up
        $dir->close();

        return true;
    } else {
        return false;
    }
}

/**
 * Recursively remove directory (or just a file)
 *
 * @param string $source
 * @param bool $delete_root
 * @param string $pattern
 * @return bool
 */
function fn_rm($source, $delete_root = true, $pattern = '')
{
    // Simple copy for a file
    if (is_file($source)) {
        $res = true;
        if (empty($pattern) || (!empty($pattern) && preg_match('/' . $pattern . '/', fn_basename($source)))) {
            $res = @unlink($source);
        }

        return $res;
    }

    // Loop through the folder
    if (is_dir($source)) {
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }
             if (fn_rm($source . '/' . $entry, true, $pattern) == false) {
                return false;
            }
        }
        // Clean up
        $dir->close();

        return ($delete_root == true && empty($pattern)) ? @rmdir($source) : true;
    } else {
        return false;
    }
}

/**
 * Get file extension
 *
 * @param string $filename
 * @return string File extension
 */
function fn_get_file_ext($filename)
{
    $i = strrpos($filename, '.');
    if ($i === false) {
        return '';
    }

    return substr($filename, $i + 1);
}

/**
 * Get directory contents
 *
 * @param string $dir directory path
 * @param bool $get_dirs get sub directories
 * @param bool $get_files
 * @param mixed $extension allowed file extensions
 * @param string $prefix file/dir path prefix
 * @return array $contents directory contents
 */
function fn_get_dir_contents($dir, $get_dirs = true, $get_files = false, $extension = '', $prefix = '', $recursive = false)
{

    $contents = array();
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {

            // $extention - can be string or array. Transform to array.
            $extension = is_array($extension) ? $extension : array($extension);

            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if ($recursive == true && is_dir($dir . '/' . $file)) {
                    $contents = fn_array_merge($contents, fn_get_dir_contents($dir . '/' . $file, $get_dirs, $get_files, $extension, $prefix . $file . '/', $recursive), false);
                }

                if ((is_dir($dir . '/' . $file) && $get_dirs == true) || (is_file($dir . '/' . $file) && $get_files == true)) {
                    if ($get_files == true && !fn_is_empty($extension)) {
                        // Check all extentions for file
                        foreach ($extension as $_ext) {
                             if (substr($file, -strlen($_ext)) == $_ext) {
                                $contents[] = $prefix . $file;
                                break;
                             }
                        }
                    } else {
                        $contents[] = $prefix . $file;
                    }
                }
            }
            closedir($dh);
        }
    }

    asort($contents, SORT_STRING);

    return $contents;
}

/**
 * Get file contents from local or remote filesystem
 *
 * @param string $location file location
 * @param string $base_dir
 * @return string $result
 */
function fn_get_contents($location, $base_dir = '')
{
    $result = '';
    $path = $base_dir . $location;

    if (!empty($base_dir) && !fn_check_path($path)) {
        return $result;
    }

    // Location is regular file
    if (is_file($path)) {
        $result = @file_get_contents($path);

    // Location is url
    } elseif (strpos($path, '://') !== false) {

        // Prepare url
        $path = str_replace(' ', '%20', $path);
        if (Bootstrap::getIniParam('allow_url_fopen') == true) {
            $result = @file_get_contents($path);
        } else {
            $result = Http::get($path);
        }
    }

    return $result;
}

/**
 * Write a string to a file
 *
 * @param string $location file location
 * @param string $content
 * @param string $base_dir
 * @param int $file_perm File access permissions for setting after writing into the file. For example 0666.
 * @param boolean $append append content if set to true
 * @return string $result
 */
function fn_put_contents($location, $content, $base_dir = '', $file_perm = DEFAULT_FILE_PERMISSIONS, $append = false)
{
    $result = '';
    $path = $base_dir . $location;

    if (!empty($base_dir) && !fn_check_path($path)) {
        return false;
    }

    fn_mkdir(dirname($path));

    $flags = 0;
    if ($append == true) {
        $flags = FILE_APPEND;
    }

    // Location is regular file
    $result = @file_put_contents($path, $content, $flags);
    if ($result !== false) {
        @chmod($path, $file_perm);
    }

    return $result;
}

/**
 * Get data from url
 *
 * @param string $val
 * @return array $val
 */
function fn_get_url_data($val)
{
    if (!preg_match('/:\/\//', $val)) {
        $val = 'http://' . $val;
    }

    $result = false;
    $_data = fn_get_contents($val);

    if (!empty($_data)) {
        $result = array(
            'name' => fn_basename($val)
        );

        // Check if the file is dynamically generated
        if (strpos($result['name'], '&') !== false || strpos($result['name'], '?') !== false) {
            $result['name'] = 'url_uploaded_file_' . uniqid(TIME);
        }
        $result['path'] = fn_create_temp_file();
        $result['size'] = strlen($_data);

        $fd = fopen($result['path'], 'wb');
        fwrite($fd, $_data, $result['size']);
        fclose($fd);
        @chmod($result['path'], DEFAULT_FILE_PERMISSIONS);

        $cache = Registry::get('temp_fs_data');

        if (!isset($cache[$result['path']])) { // cache file to allow multiple usage
            $cache[$result['path']] = $result['path'];
            Registry::set('temp_fs_data', $cache);
        }
    }

    return $result;
}

/**
 * Function get local uploaded
 *
 * @param array $val
 * @staticvar array $cache
 * @return array
 */
function fn_get_local_data($val)
{
    $cache = Registry::get('temp_fs_data');

    if (!isset($cache[$val['path']])) { // cache file to allow multiple usage
        $tempfile = fn_create_temp_file();
        if (move_uploaded_file($val['path'], $tempfile) == true) {
            @chmod($tempfile, DEFAULT_FILE_PERMISSIONS);
            $cache[$val['path']] = $tempfile;
        } else {
            $cache[$val['path']] = '';
        }

        Registry::set('temp_fs_data', $cache);
    }

    if (defined('KEEP_UPLOADED_FILES')) {
        $tempfile = fn_create_temp_file();
        fn_copy($cache[$val['path']], $tempfile);
        $val['path'] = $tempfile;
    } else {
        $val['path'] = $cache[$val['path']];
    }

    return !empty($val['size']) ? $val : false;
}

/**
 * Finds the last key in the array and applies the custom function to it.
 *
 * @param array $arr
 * @param string $fn
 * @param bool $is_first
 */
function fn_get_last_key(&$arr, $fn = '', $is_first = false)
{
    if (!is_array($arr)&&$is_first == true) {
        $arr = call_user_func($fn, $arr);

        return;
    }

    foreach ($arr as $k => $v) {
        if (is_array($v) && count($v)) {
            fn_get_last_key($arr[$k], $fn);
        } elseif (!is_array($v)&&!empty($v)) {
            $arr[$k] = call_user_func($fn, $arr[$k]);
        }
    }
}

/**
 * Filter data from file uploader
 *
 * @param string $name
 * @return array $filtered
 */
function fn_filter_uploaded_data($name, $filter_by_ext = array())
{
    $udata_local = fn_rebuild_files('file_' . $name);
    $udata_other = !empty($_REQUEST['file_' . $name]) ? $_REQUEST['file_' . $name] : array();
    $utype = !empty($_REQUEST['type_' . $name]) ? $_REQUEST['type_' . $name] : array();

    if (empty($utype)) {
        return array();
    }

    $filtered = array();

    foreach ($utype as $id => $type) {
        if ($type == 'local' && !fn_is_empty(@$udata_local[$id])) {
            $filtered[$id] = fn_get_local_data(Bootstrap::stripSlashes($udata_local[$id]));

        } elseif ($type == 'server' && !fn_is_empty(@$udata_other[$id]) && AREA == 'A') {
            fn_get_last_key($udata_other[$id], 'fn_get_server_data', true);
            $filtered[$id] = $udata_other[$id];

        } elseif ($type == 'url' && !fn_is_empty(@$udata_other[$id])) {
            fn_get_last_key($udata_other[$id], 'fn_get_url_data', true);
            $filtered[$id] = $udata_other[$id];
        }

        if (isset($filtered[$id]) && $filtered[$id] === false) {
            unset($filtered[$id]);
            fn_set_notification('E', __('error'), __('cant_upload_file'));
        }

        if (!empty($filtered[$id]) && is_array($filtered[$id]) && !empty($filtered[$id]['name'])) {
            $filtered[$id]['name'] = str_replace(' ', '_', urldecode($filtered[$id]['name'])); // replace spaces with underscores
            $ext = fn_get_file_ext($filtered[$id]['name']);

            if (!empty($filter_by_ext) && !in_array(fn_strtolower($ext), $filter_by_ext)) {
                unset($filtered[$id]);
                fn_set_notification('E', __('error'), __('text_not_allowed_to_upload_file_extension', array(
                    '[ext]' => $ext
                )));

            } elseif (in_array(fn_strtolower($ext), Registry::get('config.forbidden_file_extensions'))) {
                unset($filtered[$id]);

                fn_set_notification('E', __('error'), __('text_forbidden_file_extension', array(
                    '[ext]' => $ext
                )));
            }

        }

        if (!empty($filtered[$id]['path']) && in_array(fn_get_mime_content_type($filtered[$id]['path'], true, 'text/plain'), Registry::get('config.forbidden_mime_types'))) {
            fn_set_notification('E', __('error'), __('text_forbidden_file_mime', array(
                '[mime]' => fn_get_mime_content_type($filtered[$id]['path'], true, 'text/plain')
            )));

            unset($filtered[$id]);
        }
    }

    static $shutdown_inited;

    if (!$shutdown_inited) {
        $shutdown_inited = true;
        register_shutdown_function('fn_remove_temp_data');
    }

    return $filtered;
}

/**
 * Remove temporary files
 */
function fn_remove_temp_data()
{
    $fs_data = Registry::get('temp_fs_data');
    if (!empty($fs_data)) {
        foreach ($fs_data as $file) {
            fn_rm($file);
        }
    }
}

/**
 * Create temporary file
 *
 * @return temporary file
 */
function fn_create_temp_file()
{
    fn_mkdir(Registry::get('config.dir.cache_misc') . 'tmp');
    $tmpnam = tempnam(Registry::get('config.dir.cache_misc') . 'tmp/', 'tmp_');

    return $tmpnam;
}

/**
 * Returns correct path from url "path" component
 *
 * @param string $path
 * @return correct path
 */
function fn_get_url_path($path)
{
    $dir = dirname($path);

    if ($dir == '.' || $dir == '/') {
        return '';
    }

    return (defined('WINDOWS')) ? str_replace('\\', '/', $dir) : $dir;
}

/**
 * Check path to file
 *
 * @param string $path
 * @return bool
 */
function fn_check_path($path)
{
    $real_path = realpath($path);

    return str_replace('\\', '/', $real_path) == $path ? true : false;
}

/**
 * Gets line from file pointer and parse for CSV fields
 *
 * @param resource $f a valid file pointer to a file successfully opened by fopen(), popen(), or fsockopen().
 * @param int $length maximum line length
 * @param string $d field delimiter
 * @param string $q the field enclosure character
 * @return array structured data
 */
function fn_fgetcsv($f, $length, $d = ',', $q = '"')
{
    $list = array();
    $st = fgets($f, $length);
    if ($st === false || $st === null) {
        return $st;
    }

    if (trim($st) === '') {
        return array('');
    }

    $st = rtrim($st, "\n\r");
    if (substr($st, -strlen($d)) == $d) {
        $st .= '""';
    }

    while ($st !== '' && $st !== false) {
        if ($st[0] !== $q) {
            // Non-quoted.
            list ($field) = explode($d, $st, 2);
            $st = substr($st, strlen($field) + strlen($d));
        } else {
            // Quoted field.
            $st = substr($st, 1);
            $field = '';
            while (1) {
                // Find until finishing quote (EXCLUDING) or eol (including)
                preg_match("/^((?:[^$q]+|$q$q)*)/sx", $st, $p);
                $part = $p[1];
                $partlen = strlen($part);
                $st = substr($st, strlen($p[0]));
                $field .= str_replace($q . $q, $q, $part);
                if (strlen($st) && $st[0] === $q) {
                    // Found finishing quote.
                    list ($dummy) = explode($d, $st, 2);
                    $st = substr($st, strlen($dummy) + strlen($d));
                    break;
                } else {
                    // No finishing quote - newline.
                    $st = fgets($f, $length);
                }
            }
        }

        $list[] = $field;
    }

    return $list;
}

/**
 * Wrapper for rename with chmod
 *
 * @param string $oldname The old name. The wrapper used in oldname must match the wrapper used in newname.
 * @param string $newname The new name.
 * @param resource $context Note: Context support was added with PHP 5.0.0. For a description of contexts, refer to Stream Functions.
 *
 * @return boolean Returns TRUE on success or FALSE on failure.
 */
function fn_rename($oldname, $newname, $context = null)
{
    $result = ($context === null) ? rename($oldname, $newname) : rename($oldname, $newname, $context);
    if ($result !== false) {
        @chmod($newname, is_dir($new_name) ? DEFAULT_DIR_PERMISSIONS : DEFAULT_FILE_PERMISSIONS);
    }

    return $result;
}

/*
 * Returns pathinfo with using UTF characters.
 *
 * @param string $path
 * @param string $encoding
 * @return array
 */
function fn_pathinfo($path, $encoding = 'UTF-8')
{
    $path = fn_unified_path($path);
    $basename = explode("/", $path);
    $basename = end($basename);

    if (strpos($path, '/') === false) {
        $path = './' . $path;
    }

    $dirname = rtrim(fn_substr($path, 0, fn_strlen($path, $encoding) - fn_strlen($basename, $encoding) - 1, $encoding), '/');
    $dirname .= empty($dirname) ? '/' : '';

    if (strpos($basename, '.') !== false) {
        $_name_components = explode('.', $basename);
        $extension = array_pop($_name_components);
        $filename = implode('.', $_name_components);
    } else {
        $extension = '';
        $filename = $basename;
    }

    return array (
        'dirname' => $dirname,
        'basename' => $basename,
        'extension' => $extension,
        'filename' => $filename
    );
}

/*
 * Returns basename with using UTF characters.
 *
 * @param string $path
 * @param string $suffix
 * @param string $encoding
 * @return string
 */
function fn_basename($path, $suffix = '', $encoding = 'UTF-8')
{
    $basename = explode("/", $path);
    $basename = end($basename);

    if (!empty($suffix) && fn_substr($basename, (0 - fn_strlen($suffix, $encoding)), fn_strlen($basename, $encoding), $encoding) == $suffix) {
        $basename = fn_substr($basename, 0, (0 - fn_strlen($suffix, $encoding)), $encoding);
    }

    return $basename;
}

/**
 * Replace backslashes in windows-style path
 *
 * @param string $path path
 * @return string filtered path
 */
function fn_unified_path($path)
{
    if (defined('IS_WINDOWS')) {
        $path = str_replace('\\', '/', $path);
    }

    return $path;
}

/**
 * Connect to ftp server
 *
 * @param array $settings options
 * @return boolean true if connected successfully and working directory is correct, false - otherwise
 */
function fn_ftp_connect($settings)
{
    $result = true;

    if (function_exists('ftp_connect')) {
        if (!empty($settings['ftp_hostname'])) {
            $ftp_port = !empty($settings['ftp_port']) ? $settings['ftp_port'] : '21';
            if (substr_count($settings['ftp_hostname'], ':') > 0) {
                $start_pos = strrpos($settings['ftp_hostname'], ':');
                $ftp_port = substr($settings['ftp_hostname'], $start_pos + 1);
                $settings['ftp_hostname'] = substr($settings['ftp_hostname'], 0, $start_pos);
            }

            $ftp = @ftp_connect($settings['ftp_hostname'], $ftp_port);
            if (!empty($ftp)) {
                if (@ftp_login($ftp, $settings['ftp_username'], $settings['ftp_password'])) {

                    ftp_pasv($ftp, true);

                    if (!empty($settings['ftp_directory'])) {
                        @ftp_chdir($ftp, $settings['ftp_directory']);
                    }

                    $files = ftp_nlist($ftp, '.');
                    if (!empty($files) && in_array('config.php', $files)) {
                        Registry::set('ftp_connection', $ftp);
                    } else {
//                        fn_set_notification('E', __('error'), __('text_uc_ftp_cart_directory_not_found'));
                        $result = false;
                    }
                } else {
//                    fn_set_notification('E', __('error'), __('text_uc_ftp_login_failed'));
                    $result = false;
                }
            } else {
//                fn_set_notification('E', __('error'), __('text_uc_ftp_connect_failed'));
                $result = false;
            }
        }
    } else {
//        fn_set_notification('E', __('error'), __('text_uc_no_ftp_module'));
        $result = false;
    }

    return $result;
}

function fn_fpt_chmod_file($filename, $perm = DEFAULT_FILE_PERMISSIONS, $recursive = false)
{
    $result = false;

    $ftp = Registry::get('ftp_connection');
    if (is_resource($ftp)) {
        $dest = dirname($filename);
        $dest = rtrim($dest, '/') . '/'; // force adding trailing slash to path

        $rel_path = str_replace(Registry::get('config.dir.root') . '/', '', $dest);
        $cdir = ftp_pwd($ftp);

        if (empty($rel_path)) { // if rel_path is empty, assume it's root directory
            $rel_path = $cdir;
        }

        if (@ftp_chdir($ftp, $rel_path)) {
            $result = @ftp_site($ftp, 'CHMOD ' . sprintf('0%o', $perm) . ' ' . fn_basename($filename));

            if ($recursive) {
                $path = fn_normalize_path($cdir . '/' . $rel_path . fn_basename($filename));

                if (is_dir($path)) {
                    $_files = fn_get_dir_contents($path, true, true, '', '', true);

                    if (!empty($_files)) {
                        foreach ($_files as $_file) {
                            fn_fpt_chmod_file($path . '/' . $_file, $perm, false);
                        }
                    }

                }
            }

            ftp_chdir($ftp, $cdir);
        }
    }

    return $result;
}
