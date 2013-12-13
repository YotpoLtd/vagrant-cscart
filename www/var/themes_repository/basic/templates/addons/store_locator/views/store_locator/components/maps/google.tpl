{if !$smarty.capture.$map_provider_api}
<script src="http://www.google.com/jsapi"></script>
<script src="http://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false&amp;language={$smarty.const.CART_LANGUAGE|fn_store_locator_google_langs}" type="text/javascript"></script>
{script src="/js/addons/store_locator/google.js"}
{capture name="`$map_provider_api`"}Y{/capture}
{/if}

<script type="text/javascript">
    //<![CDATA[
    {literal}
    (function(_, $) {

        {/literal}
        var storeData = [
            {foreach from=$store_locations item="loc" name="st_loc_foreach" key="key"}
            {ldelim}
                'store_location_id' : '{$loc.store_location_id}',
                'country' :  '{$loc.country|escape:javascript nofilter}',
                'latitude' : {$loc.latitude|doubleval},
                'longitude' : {$loc.longitude|doubleval},
                'name' :  '{$loc.name|escape:javascript nofilter}',
                'description' : '{$loc.description|escape:javascript nofilter}',
                'city' : '{$loc.city|escape:javascript nofilter}',
                'country_title' : '{$loc.country_title|escape:javascript nofilter}'
                {rdelim}
            {if !$smarty.foreach.st_loc_foreach.last},{/if}
            {/foreach}
        ];
        {literal}

        options = {
            {/literal}
            'latitude': {$smarty.const.STORE_LOCATOR_DEFAULT_LATITUDE|doubleval},
            'longitude': {$smarty.const.STORE_LOCATOR_DEFAULT_LONGITUDE|doubleval},
            'map_container': '{$map_container}',
            'zoom_control':{if $sl_settings.google_zoom_control == 'Y'} true {else} false {/if},
            'scale_control':{if $sl_settings.google_scale_control == 'Y'} true {else} false {/if},
            'street_view_control':false,
            'zoom': {if !empty($sl_settings.google_zoom)} {$sl_settings.google_zoom} {else} 16 {/if},
            'map_type_control':{if $sl_settings.google_map_type_control == 'Y'} true {else} false {/if},
            'storeData': storeData,
            {literal}
        };

        Tygh.$(document).ready(function(){
            $.ceMap('init', options);
            google.setOnLoadCallback(Tygh.$.ceMap('show'));
        });

    }(Tygh, Tygh.$));
    {/literal}
    //]]>
</script>


