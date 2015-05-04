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

namespace Tygh\Shippings;

use Tygh\Registry;

class Shippings
{

    /**
     * Init shippings
     */
    public static function init()
    {

    }

    /**
     * Prepare products list for get shippings
     *
     * @param  array $products Products list with products data
     * @param  array $location User location
     * @return array Product groups
     */
    public static function groupProductsList($products, $location)
    {
        $groups = array();

        foreach ($products as $key_product => $product) {
            if (fn_allowed_for('ULTIMATE')) {
                $company_id = Registry::ifGet('runtime.company_id', fn_get_default_company_id());
            } else {
                $company_id = $product['company_id'];
            }

            if (empty($groups[$company_id])) {
                $origination = self::_getOriginationData($company_id);
                $groups[$company_id] = array(
                    'name' => $origination['name'],
                    'company_id' => (int) $company_id,
                    'origination' => $origination,
                    'location' => $location,
                );
            }
            $groups[$company_id]['products'][$key_product] = $product;
        }

        fn_set_hook('shippings_group_products_list', $products, $groups);

        foreach ($groups as $key_group => $group) {
            $groups[$key_group]['package_info'] = self::_getPackageInfo($group);
            unset($groups[$key_group]['origination']);
            unset($groups[$key_group]['location']);

            $all_edp_free_shipping = true;
            $all_free_shipping = true;
            $free_shipping = true;
            $shipping_no_required = true;
            foreach ($group['products'] as $product) {
                if ($product['is_edp'] != 'Y' || $product['edp_shipping'] == 'Y') {
                    $all_edp_free_shipping = false;
                }
                if (empty($product['free_shipping']) || $product['free_shipping'] != 'Y') {
                    $all_free_shipping = false;
                }
                if (($product['is_edp'] != 'Y' || $product['edp_shipping'] == 'Y') && (empty($product['free_shipping']) || $product['free_shipping'] != 'Y')) {
                    $free_shipping = false;
                }
                if (empty($product['shipping_no_required']) || $product['shipping_no_required'] != 'Y') {
                    $shipping_no_required = false;
                }
            }
            $groups[$key_group]['all_edp_free_shipping'] = $all_edp_free_shipping;
            $groups[$key_group]['all_free_shipping'] = $all_free_shipping;
            $groups[$key_group]['free_shipping'] = $free_shipping;
            $groups[$key_group]['shipping_no_required'] = $shipping_no_required;
        }

        return array_values($groups);
    }

    /**
     * Get origination data
     *
     * @param  array $company_id Company ID
     * @return array Origination data
     */
    private static function _getOriginationData($company_id)
    {
        $data = array();

        if (empty($company_id) || fn_allowed_for('ULTIMATE')) {
            $data = array(
                'name' => Registry::get('settings.Company.company_name'),
                'address' => Registry::get('settings.Company.company_address'),
                'city' => Registry::get('settings.Company.company_city'),
                'country' => Registry::get('settings.Company.company_country'),
                'state' => Registry::get('settings.Company.company_state'),
                'zipcode' => Registry::get('settings.Company.company_zipcode'),
                'phone' => Registry::get('settings.Company.company_phone'),
                'fax' => Registry::get('settings.Company.company_fax'),
            );
        } else {
            $company_data = fn_get_company_data($company_id);
            $data = array(
                'name' => $company_data['company'],
                'address' => $company_data['address'],
                'city' => $company_data['city'],
                'country' => $company_data['country'],
                'state' => $company_data['state'],
                'zipcode' => $company_data['zipcode'],
                'phone' => $company_data['phone'],
                'fax' => $company_data['fax'],
            );
        }

        return $data;
    }

    /**
     * Get package information
     *
     * @param  array $group Group information
     * @return array Package information
     */
    private static function _getPackageInfo($group)
    {
        $package_info = array();
        $package_info['C'] = 0;
        $package_info['W'] = 0;
        $package_info['I'] = 0;
        $package_info['shipping_freight'] = 0;

        if (is_array($group['products'])) {
            foreach ($group['products'] as $key_product => $product) {
                if (($product['is_edp'] == 'Y' && $product['edp_shipping'] != 'Y') || !empty($product['free_shipping']) && $product['free_shipping'] == 'Y') {
                    continue;
                }

                $package_info['C'] += !empty($product['subtotal']) ? $product['subtotal'] : !empty($product['base_price']) ? ($product['base_price'] * $product['amount']) : 0;
                $package_info['W'] += !empty($product['weight']) ? $product['weight'] * $product['amount'] : 0;
                $package_info['I'] += $product['amount'];
                if (isset($product['shipping_freight'])) {
                    $package_info['shipping_freight'] += $product['shipping_freight'] * $product['amount'];
                }
            }
        }

        $package_info['W'] = !empty($package_info['W']) ? sprintf("%.2f", $package_info['W']) : '0.01';

        $package_groups = array(
            'personal' => array(),
            'global' => array(
                'products' => array(),
                'amount' => 0,
            ),
        );
        foreach ($group['products'] as $cart_id => $product) {
            if (empty($product['shipping_params']) || (empty($product['shipping_params']['min_items_in_box']) && empty($product['shipping_params']['max_items_in_box']))) {
                if (!(($product['is_edp'] == 'Y' && $product['edp_shipping'] != 'Y') || !empty($product['free_shipping']) && $product['free_shipping'] == 'Y')) {
                    $package_groups['global']['products'][$cart_id] = $product['amount'];
                    $package_groups['global']['amount'] += $product['amount'];
                }

            } else {
                if (!isset($package_groups['personal'][$product['product_id']])) {
                    $package_groups['personal'][$product['product_id']] = array(
                        'shipping_params' => $product['shipping_params'],
                        'amount' => 0,
                        'products' => array(),
                    );
                }

                if (!(($product['is_edp'] == 'Y' && $product['edp_shipping'] != 'Y') || !empty($product['free_shipping']) && $product['free_shipping'] == 'Y')) {
                    $package_groups['personal'][$product['product_id']]['amount'] += $product['amount'];
                    $package_groups['personal'][$product['product_id']]['products'][$cart_id] = $product['amount'];
                }
            }
        }

        // Divide the products into a separate packages
        $packages = array();

        if (!empty($package_groups['personal'])) {
            foreach ($package_groups['personal'] as $product_id => $package_products) {

                while ($package_products['amount'] > 0) {
                    if (!empty($package_products['shipping_params']['min_items_in_box']) && $package_products['amount'] < $package_products['shipping_params']['min_items_in_box']) {

                        list($package_products_pack, $package_size) = self::_getPackageByAmount($package_products['amount'], $package_products['products']);

                        foreach ($package_products_pack as $cart_id => $amount) {
                            $package_groups['global']['products'][$cart_id] = isset($package_groups['global']['products'][$cart_id]) ? $package_groups['global']['products'][$cart_id] : 0;
                            $package_groups['global']['products'][$cart_id] += $amount;
                            $package_groups['global']['amount'] += $amount;
                        }
                    } else {
                        $amount = empty($package_products['shipping_params']['max_items_in_box']) ? $package_products['amount'] : $package_products['shipping_params']['max_items_in_box'];

                        list($package_products_pack, $package_size) = self::_getPackageByAmount($amount, $package_products['products']);

                        $packages[] = array(
                            'shipping_params' => $package_products['shipping_params'],
                            'products' => $package_products_pack,
                            'amount' => $package_size,
                        );
                    }

                    // Decrease the current product amount in the global package groups
                    foreach ($package_products_pack as $cart_id => $amount) {
                        $package_products['products'][$cart_id] -= $amount;
                    }
                    $package_products['amount'] -= $package_size;
                }

            }
        }

        if (!empty($package_groups['global']['products'])) {
            $packages[] = $package_groups['global'];
        }

        // Calculate the package additional info (weight, cost)
        foreach ($packages as $package_id => $package) {
            $weight = 0;
            $cost = 0;

            foreach ($package['products'] as $cart_id => $amount) {
                $_weight = !empty($group['products'][$cart_id]['weight']) ? $group['products'][$cart_id]['weight'] : 0;
                $price = !empty($group['products'][$cart_id]['price']) ? $group['products'][$cart_id]['price'] : !empty($group['products'][$cart_id]['base_price']) ? $group['products'][$cart_id]['base_price'] : 0;
                $weight += $_weight * $amount;
                $cost += $price * $amount;
            }

            $packages[$package_id]['weight'] = !empty($weight) ? $weight : 0.1;
            $packages[$package_id]['cost'] = $cost;
        }

        $package_info['packages'] = $packages;
        $package_info['origination'] = $group['origination'];
        $package_info['location'] = $group['location'];

        return $package_info;
    }

    /**
     * Get package by amount
     *
     * @param  array $amount   Amount products in package group
     * @param  array $products Products list in package group
     * @return array Products list and package size
     */
    private static function _getPackageByAmount($amount, $products)
    {
        $data = array();
        $package_size = 0;

        foreach ($products as $cart_id => $product_amount) {
            if ($product_amount == 0 || $amount == 0) {
                continue;
            }
            $data[$cart_id] = min($product_amount, $amount);
            $package_size += $product_amount;
            $amount -= $product_amount;

            if ($amount <= 0) {
                break;
            }
        }

        return array($data, $package_size);
    }

    /**
     * Get shippings list
     *
     * @param  array $group Group products information
     * @return array Shippings list
     */
    public static function getShippingsList($group, $lang = CART_LANGUAGE, $area = AREA)
    {
        $shippings = self::_getCompanyShippings($group['company_id']);

        $condition = "";
        if ($area == 'C') {
            $condition = "AND (" . fn_find_array_in_set($_SESSION['auth']['usergroup_ids'], '?:shippings.usergroup_ids', true) . ")";
        }

        fn_set_hook('shippings_get_shippings_list', $group, $shippings, $condition);

        $package_weight = $group['package_info']['W'];

        $shippings_info = db_get_hash_array(
            "SELECT "
                . "?:shippings.shipping_id, "
                . "?:shipping_descriptions.shipping, "
                . "?:shipping_descriptions.delivery_time, "
                . "?:shippings.rate_calculation, "
                . "?:shippings.service_params, "
                . "?:shippings.destination, "
                . "?:shippings.min_weight, "
                . "?:shippings.max_weight, "
                . "?:shippings.service_id, "
                . "?:shipping_services.module, "
                . "?:shipping_services.code as service_code "
            . "FROM ?:shippings "
                . "LEFT JOIN ?:shipping_descriptions "
                    . "ON ?:shippings.shipping_id = ?:shipping_descriptions.shipping_id "
                . "LEFT JOIN ?:shipping_services "
                    . "ON ?:shipping_services.service_id = ?:shippings.service_id "
            . "WHERE ?:shippings.status = ?s "
                . "AND ?:shippings.shipping_id IN (?n) "
                . "AND (?:shippings.min_weight <= ?d "
                . "AND (?:shippings.max_weight >= ?d OR ?:shippings.max_weight = 0.00)) "
                . "AND ?:shipping_descriptions.lang_code = ?s "
                . "?p "
            . "ORDER BY ?:shippings.position ",
            'shipping_id',
            'A', $shippings, $package_weight, $package_weight, $lang, $condition
        );

        foreach ($shippings_info as $key => $shipping_info) {
            $shippings_info[$key]['rate_info'] = self::_getRateInfoByLocation($shipping_info['shipping_id'], $group['package_info']['location']);
            $shippings_info[$key]['service_params'] = !empty($shippings_info[$key]['service_params']) ? unserialize($shippings_info[$key]['service_params']) : array();
        }

        return $shippings_info;
    }

    /**
     * Get shipping for test
     *
     * @param  int   $shipping_id    Shipping ID
     * @param  int   $service_id     Service ID
     * @param  array $service_params Service configurations
     * @param  array $package_info   Package info
     * @return array Shipping
     */
    public static function getShippingForTest($shipping_id, $service_id, $service_params, $package_info, $lang = CART_LANGUAGE)
    {
        $shipping_info = db_get_row(
            "SELECT "
                . "?:shippings.shipping_id, "
                . "?:shipping_descriptions.shipping, "
                . "?:shipping_descriptions.delivery_time, "
                . "?:shippings.rate_calculation, "
                . "?:shippings.service_params, "
                . "?:shippings.destination, "
                . "?:shippings.min_weight, "
                . "?:shippings.max_weight, "
                . "?:shippings.service_id, "
                . "?:shipping_services.module, "
                . "?:shipping_services.code as service_code "
            . "FROM ?:shippings "
                . "LEFT JOIN ?:shipping_descriptions "
                    . "ON ?:shippings.shipping_id = ?:shipping_descriptions.shipping_id "
                . "LEFT JOIN ?:shipping_services "
                    . "ON ?:shipping_services.service_id = ?i "
            . "WHERE ?:shippings.shipping_id = ?i "
                . "AND ?:shipping_descriptions.lang_code = ?s "
            . "ORDER BY ?:shippings.position ",
            $service_id, $shipping_id, $lang
        );

        $shipping_info['rate_info'] = self::_getRateInfoByLocation($shipping_id, $package_info['location']);
        $shipping_info['rate_calculation'] = 'R';
        $shipping_info['service_params'] = !empty($service_params) ? $service_params : unserialize($shipping_info['service_params']);
        $shipping_info['package_info'] = $package_info;

        return $shipping_info;
    }

    /**
     * Get shippings list for company
     *
     * @param  int   $company_id Company ID
     * @return array Shippings array
     */
    private static function _getCompanyShippings($company_id)
    {
        if (fn_allowed_for('ULTIMATE')) {
            $shippings = db_get_fields("SELECT shipping_id FROM ?:shippings WHERE status = ?s", 'A');
        } else {
            $shippings = explode(',', db_get_field("SELECT shippings FROM ?:companies WHERE company_id = ?i", $company_id));
            $shippings = db_get_fields("SELECT shipping_id FROM ?:shippings WHERE (company_id = ?i OR (company_id = ?i AND shipping_id IN (?a))) AND status = ?s", $company_id, 0, $shippings, 'A');
        }

        return $shippings;
    }

    /**
     * Get rate information by user location
     *
     * @param  int   $shipping_info Shipping information
     * @param  int   $location      User location
     * @return array Rate information
     */
    private static function _getRateInfoByLocation($shipping_id, $location)
    {
        $destination_id = fn_get_available_destination($location);

        $rate_info = db_get_row("SELECT rate_id, rate_value FROM ?:shipping_rates WHERE shipping_id = ?i AND destination_id = ?i ORDER BY destination_id desc", $shipping_id, $destination_id);
        if (!empty($rate_info)) {
            $rate_info['rate_value'] = unserialize($rate_info['rate_value']);
        } else {
            $rate_info = array();
        }

        return $rate_info;
    }

    /**
     * Calculate rates
     *
     * @param  array $shippings List all shippings with information about them
     * @return array Rates list
     */
    public static function calculateRates($shippings)
    {
        $mode = array(
            'real' => array(),
            'manual' => array(),
        );
        $rates = array();

        foreach ($shippings as $shipping) {
            if ($shipping['rate_calculation'] == 'R') {
                $shipping['keys']['mode_key'] = count($mode['real']);
                $mode['real'][] = $shipping;
            } else {
                $shipping['keys']['mode_key'] = count($mode['manual']);
                $mode['manual'][] = $shipping;
            }
        }

        if (!empty($mode['real'])) {
            $rates = self::_calculateRealTimeRates($mode['real']);
            foreach ($rates as $key_rate => $rate) {
                if ($rate['price'] !== false) {
                    $rates[$key_rate]['price'] += self::_calculateManualRealRate($mode['real'][$rate['keys']['mode_key']]);
                }
                unset($rates[$key_rate]['keys']['mode_key']);
            }
        }

        if (!empty($mode['manual'])) {
            foreach ($mode['manual'] as $shipping) {
                $rate = self::_calculateManualRate($shipping);
                unset($shipping['keys']['mode_key']);
                $rates[] = array(
                    'price' => $rate,
                    'keys' => !empty($shipping['keys']) ? $shipping['keys'] : array(),
                );
            }
        }

        return array_values($rates);
    }

    /**
     * Calculate realtime rates
     *
     * @param  array $shippings List realtime shippings
     * @return array Rates list
     */
    private static function _calculateRealTimeRates($shippings)
    {
        $_rates = array();
        RealtimeServices::clearStack();

        foreach ($shippings as $shipping_key => $shipping) {
            $error = RealtimeServices::register($shipping_key, $shipping);
            if (!empty($error)) {
                $_rates[] = array(
                    'price' => false,
                    'keys' => $shipping['keys'],
                    'error' => $error,
                );
            }
        }

        $rates = RealtimeServices::getRates();

        foreach ($rates as $rate) {
            $_rates[] = array(
                'price' => $rate['price'],
                'keys' => $shippings[$rate['shipping_key']]['keys'],
                'error' => $rate['error']
            );
        }

        return $_rates;
    }

    /**
     * Calculate manual rate
     *
     * @param  array $shipping Manual shipping
     * @return float Rate
     */
    private static function _calculateManualRate($shipping)
    {
        if (empty($shipping['rate_info']['rate_value'])) {
            return false;
        }

        $base_cost = $shipping['package_info']['C'];
        $rate = 0;

        foreach ($shipping['package_info'] as $type => $amount) {
            if (isset($shipping['rate_info']['rate_value'][$type]) && is_array($shipping['rate_info']['rate_value'][$type])) {
                $rate_value = array_reverse($shipping['rate_info']['rate_value'][$type], true);
                foreach ($rate_value as $rate_amount => $data) {
                    if ($rate_amount < $amount || ($rate_amount == 0.00 && $amount == 0.00)) {
                        $value = $data['type'] == 'F' ? $data['value'] : (($base_cost * $data['value']) / 100);
                        $per_unit = (!empty($data['per_unit']) && $data['per_unit'] == 'Y') ? $shipping['package_info'][$type] : 1;

                        $rate += $value * $per_unit;

                        break;
                    }
                }
            }
        }

        return fn_format_price($rate);
    }

    /**
     * Calculate manual rate for real rate
     *
     * @param  array $shipping Manual shipping
     * @return float Rate
     */
    private static function _calculateManualRealRate($shipping)
    {
        $rate_info = db_get_row("SELECT rate_id, rate_value FROM ?:shipping_rates WHERE shipping_id = ?i AND destination_id = 0 ORDER BY destination_id desc", $shipping['shipping_id']);
        if (!empty($rate_info)) {
            $rate_info['rate_value'] = unserialize($rate_info['rate_value']);
        } else {
            return 0;
        }

        $base_cost = $shipping['package_info']['C'];
        $rate = 0;

        foreach ($shipping['package_info'] as $type => $amount) {
            if (isset($rate_info['rate_value'][$type]) && is_array($rate_info['rate_value'][$type])) {
                $rate_value = array_reverse($rate_info['rate_value'][$type], true);
                foreach ($rate_value as $rate_amount => $data) {
                    if ($rate_amount < $amount || ($rate_amount == 0.00 && $amount == 0.00)) {
                        $value = $data['type'] == 'F' ? $data['value'] : (($base_cost * $data['value']) / 100);
                        $per_unit = (!empty($data['per_unit']) && $data['per_unit'] == 'Y') ? $shipping['package_info'][$type] : 1;

                        $rate += $value * $per_unit;

                        break;
                    }
                }
            }
        }

        return fn_format_price($rate);
    }

}
