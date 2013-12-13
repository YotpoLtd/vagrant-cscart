<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier<br>
 * Name:     price<br>
 * Purpose:  getting formatted price with grouped thousands and
 *           decimal separators
 * Example:  {$price|price:"2":".":","}
 * -------------------------------------------------------------
 */

function smarty_modifier_format_price($price, $currency, $span_id, $class = '', $is_secondary = false)
{
    $value = fn_format_rate_value($price, $number_type, $currency['decimals'], $currency['decimals_separator'], $currency['thousands_separator'], $currency['coefficient']);

    if (!empty($class)) {
        $currency['symbol'] = '<span class="' . $class . '">' . $currency['symbol'] . '</span>';
    }

    if (!empty($span_id) && $is_secondary == true) {
        $span_id = 'sec_' . $span_id;
    }

    if (!empty($class) || !empty($span_id)) {
        $data = array (
            '<span' . (!empty($span_id) ? ' id="' . $span_id . '"' : '') . (!empty($class) ? ' class="' . $class . '"' : '') . '>',
            $value,
            '</span>',
        );
    } else {
        $data = array($value);
    }

    if ($currency['after'] == 'Y') {
        array_push($data, '&nbsp;' . $currency['symbol']);
    } else {
        array_unshift($data, $currency['symbol']);
    }

    return implode('', $data);

}

/* vim: set expandtab: */
