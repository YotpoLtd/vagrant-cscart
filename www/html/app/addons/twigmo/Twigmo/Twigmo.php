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

namespace Twigmo;

use Tygh\Http;
use Twigmo\Api\ApiData;

class Twigmo
{
    public $response_doc;
    public $response_data;
    public $meta;
    public $errors;

    private $service_url;
    private $access_id;
    private $secret_access_key;
    private $action_list;
    private $basic_auth = array();
    private $api_version = TWG_DEFAULT_API_VERSION;
    private $api_format = TWG_DEFAULT_DATA_FORMAT;

    public function __construct(
        $service_url = '',
        $access_id = '',
        $secret_access_key= '',
        $basic_username = '',
        $basic_password = ''
    ) {
        if (!empty($basic_username)) {
            $this->basic_auth = array (
                $basic_username,
                $basic_password
            );
        }

        $this->service_url = $service_url;
        $this->access_id = $access_id;
        $this->secret_access_key = $secret_access_key;
    }

    public function setApiVersion($version)
    {
        $this->api_version = $version;
    }

    public function setApiFormat($format)
    {
        $this->api_format = $format;
    }

    /*
     * Get signature for the request,
     * basing on request params
     */
    public static function getSignature($params, $secret_access_key)
    {
        unset($params['signature']);

        ksort($params);

        $hash_params = array();

        foreach ($params as $k => $v) {
            if (strlen($v) > TWG_MAX_HASH_PARAM_LEN) {
                $v = substr($v, 0, TWG_MAX_HASH_PARAM_LEN);
            }

            $hash_params[] = $k . "=" . $v;
        }

        $hash_request = implode("&", $hash_params);
        $signature_string = $hash_request;

        $hash = hash_hmac('sha256', $signature_string, $secret_access_key, true);

        $signature = base64_encode($hash);

        return $signature;
    }

    /**
     * Get license text from twigmo.com
     * @param  string $license
     * @return string
     */
    public static function getLicenseText($license)
    {
        if (empty($license)) {
            $lang_code = strtolower(CART_LANGUAGE) != 'ru' ? 'en' : 'ru';
            $url = (defined('HTTPS') ? 'https' : 'http')
                . '://twigmo.com/download/license_'
                . $lang_code
                . '.js';

            $resp = Http::get($url);
            $search =  array(
                "/'Twigmo/",
                "/'С/",
                "/\\\\'/",
                "/twigmo_license_text =/",
                "/';$/",
                "/\\\\n\\\\/",
                "/'$/"
            );
            $replace = array("Twigmo", "С", "'");
            $license = preg_replace($search, $replace, $resp);
        }

        return $license;
    }

    /*
     * Validates Twigmo auth signature
     */
    public static function validateAuth($secret_access_key)
    {
        $signature = self::getSignature($_REQUEST, $secret_access_key);
        if (empty($_REQUEST['signature']) || ($_REQUEST['signature'] != $signature)) {
            return false;
        }

        return true;
    }

    /*
     * Send api request and return parsed data as array
     */
    public function sendRequest($params, $method)
    {
        // use 'fn.requests.php' methods
        // to use separately from cart
        // 'fn_https_request', 'fn_http_request'
        // methods  should be replaced by new
        // functions

        $url = $this->service_url;
        if (empty($url)) {
            return false;
        }

        $response = Http::get($url, $params);
        $this->response_doc = $response;

        $parser = new ApiData($this->api_version);

        if (!$parser->parseResponse($response, $this->api_format)) {
            return false;
        }

        $this->response_data = $parser->getData();
        $this->meta = $parser->getMeta();
        $this->errors = $parser->getErrors();

        if (!empty($this->errors)) {
            return false;
        }

        return true;
    }

    /*
     * Post data to store
     */
    public function postData(
        $data,
        $object,
        $action,
        $additional_params = array()
    ) {
        $request_data = ApiData::applyFormat($data);

        $params = array (
            'data' => base64_encode($request_data),
            'object' => $object,
            'action' => $action,
        );
        $params = array_merge($params, $additional_params);

        return $this->sendRequest($params, 'POST');
    }

    /*
     * Check environment for correct addon work
     * @return array
     */
    public static function checkRequirements()
    {
        $errors = array();
        if (!function_exists('hash_hmac')){
            $errors[] = str_replace('[php_module_name]', 'Hash', __('twgadmin_phpmod_required'));
        }
        return $errors;
    }

    /*
     * Prepare params and url for the request
     * remove not needed params
     * process array params
     * sign in request if necessary
     */
    private function getRequest($request_url = '', $request_params = array())
    {
        // Remove auto passed request params
        unset($request_params['is_ajax']);
        foreach ($request_params as $k => $v) {
            if (is_array($v)) {
                $request_params[$k] = serialize($v);
            }
        }

        $url_data = parse_url($request_url);

        // get url without request data
        $scheme =  !(empty($url_data['scheme'])) ? $url_data['scheme'] : 'https';
        $url = $scheme . '://' . $url_data['host'] . $url_data['path'];

        // Add  params from the url
        if (!empty($url_data['query'])) {
            parse_str($url_data['query'], $params);
            $params = array_merge($params, $request_params);
        } else {
            $params = $request_params;
        }

        if ($this->api_version != TWG_DEFAULT_API_VERSION) {
            $params['api_version'] = $this->api_version;
        }

        if ($this->api_format != TWG_DEFAULT_DATA_FORMAT) {
            $params['format'] = $this->api_format;
        }

        if (empty($this->access_id) || empty($this->secret_access_key)) {
            return array($url, $params);
        }

        // Add access data
        $params['date'] = gmdate("Y-m-d\TH:i:s\Z");
        $params['access_id'] = $this->access_id;

        $params['signature'] = self::getSignature($params, $this->secret_access_key);

        return array($url, $params);
    }
}
