{foreach from=$order.gift_certificates item="item"}
SPL{$_d}INVOICE{$_d}{$order.timestamp|date_format:"%m/%d/%Y"}{$_d}{$addons.quickbooks.accnt_product}{$_d}{$order.b_lastname}, {$order.b_firstname}{$_d}{$addons.quickbooks.trns_class}{$_d}-{$item.amount}{$_d}{$order.order_id}{$_d}GIFT CERTIFICATE: {$item.gift_ceert_code}{$_d}{$item.amount}{$_d}-1{$_d}GIFT CERTIFICATE{$_d}N{$_d}
{/foreach}