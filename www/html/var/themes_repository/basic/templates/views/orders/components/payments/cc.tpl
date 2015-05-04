{script src="js/lib/maskedinput/jquery.maskedinput.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div class="clearfix">
    <div class="credit-card">
            <div class="control-group">
                <label for="credit_card_number_{$id_suffix}" class="cm-required">{__("card_number")}</label>
                <input size="35" type="text" id="credit_card_number_{$id_suffix}" name="payment_info[card_number]" value="" class="cm-cc-number input-text cm-autocomplete-off" />
                <ul class="cc-icons-wrap cc-icons cm-cc-icons">
                    <li class="cc-icon cc-default cm-cc-default"><span class="default">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-visa"><span class="visa">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-visa_electron"><span class="visa-electron">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-mastercard"><span class="mastercard">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-maestro"><span class="maestro">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-amex"><span class="american-express">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-discover"><span class="discover">&nbsp;</span></li>
                </ul>
            </div>
    
            <div class="control-group">
                <label for="credit_card_month_{$id_suffix}" class="cm-required">{__("valid_thru")}</label>
                <label for="credit_card_year_{$id_suffix}" class="cm-required hidden"></label>
                <input type="text" id="credit_card_month_{$id_suffix}" name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="cm-cc-exp-month input-text-short" />&nbsp;&nbsp;/&nbsp;&nbsp;<input type="text" id="credit_card_year_{$id_suffix}"  name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="cm-cc-exp-year input-text-short" />&nbsp;
            </div>
    
            <div class="control-group">
                <label for="credit_card_name_{$id_suffix}" class="cm-required">{__("cardholder_name")}</label>
                <input size="35" type="text" id="credit_card_name_{$id_suffix}" name="payment_info[cardholder_name]" value="" class="cm-cc-name input-text uppercase" />
            </div>
    </div>
    
    <div class="control-group cvv-field">
        <label for="credit_card_cvv2_{$id_suffix}" class="cm-required cm-integer cm-autocomplete-off">{__("cvv2")}</label>
        <input type="text" id="credit_card_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 input-text-short" />

        <div class="cvv2">{__("what_is_cvv2")}
            <div class="cvv2-note">

                <div class="card-info clearfix">
                    <div class="cards-images">
                        <img src="{$images_dir}/visa_cvv.png" alt="" />
                    </div>
                    <div class="cards-description">
                        <h5>{__("visa_card_discover")}</h5>
                        <p>{__("credit_card_info")}</p>
                    </div>
                </div>
                <div class="card-info ax clearfix">
                    <div class="cards-images">
                        <img src="{$images_dir}/express_cvv.png" alt="" />
                    </div>
                    <div class="cards-description">
                        <h5>{__("american_express")}</h5>
                        <p>{__("american_express_info")}</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function(_, $) {
    $.ceEvent('on', 'ce.commoninit', function() {

        var icons = $('.cm-cc-icons li');

        if($(".cm-cc-number").data('rawMaskFn') == undefined) {

            $(".cm-cc-number").mask("9999 9999 9999 9?999", {
                placeholder: ' '
            });

            $(".cm-cc-cvv2").mask("999?9", {
                placeholder: ''
            });

            $(".cm-cc-exp-month, .cm-cc-exp-year").mask("99", {
                placeholder: ''
            });

            $('.cm-cc-number').validateCreditCard(function(result) {
                icons.removeClass('active');
                if (result.card_type) {
                    icons.filter('.cm-cc-' + result.card_type.name).addClass('active');

                    if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) != -1) {
                        $('.cm-cc-cvv2').parent('label').removeClass('cm-required');
                    } else {
                        $('.cm-cc-cvv2').parent('label').addClass('cm-required');
                    }
                }
            });

        }
    });
})(Tygh, Tygh.$);
</script>