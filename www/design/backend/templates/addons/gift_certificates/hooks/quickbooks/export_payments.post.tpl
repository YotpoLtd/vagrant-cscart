{if $order.use_gift_certificates}
{foreach from=$order.use_gift_certificates item="item" key="code"}
SPL{$_d}INVOICE{$_d}{$order.timestamp|date_format:"%m/%d/%Y"}{$_d}{$addons.quickbooks.accnt_discount}{$_d}{$order.b_lastname}, {$order.b_firstname}{$_d}{$addons.quickbooks.trns_class}{$_d}{$item.cost}{$_d}{$order.order_id}{$_d}GIFT CERTIFICATE: {$code}{$_d}-{$item.cost}{$_d}-1{$_d}GIFT CERTIFICATE{$_d}N{$_d}
{/foreach}
{/if}