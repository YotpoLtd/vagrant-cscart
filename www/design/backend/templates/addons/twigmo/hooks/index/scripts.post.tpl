<script>
    //<![CDATA[
    {literal}
    Tygh.$(function (_, $) {
        _.tr("checkout_terms_n_conditions_alert", "{/literal}{__("checkout_terms_n_conditions_alert")}{literal}");
        
        $.ceFormValidator('registerValidator', {
            class: 'cm-check-agreement',
            message: _.tr("checkout_terms_n_conditions_alert"),
            func: function(id) {
                return $('#' + id).prop('checked');
            }
        });
        
        function fn_tw_copy_email() {
            var tw_email = $('#elm_tw_email').val();
            $('#elm_reset_tw_password').attr(
                'href', 
                'http://twigmo.com/svc/reset_password.php?email=' + tw_email
            );
        }
        
        
        function fn_tw_check_agreement() {
            if (!$('#id_accept_terms').attr('checked')) {
                return false;
            }
            return true;
        }
        
    }(Tygh, Tygh.$));
    {/literal}
    //]]>
</script>