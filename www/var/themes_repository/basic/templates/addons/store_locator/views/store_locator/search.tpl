{assign var="map_provider" value=$addons.store_locator.map_provider}
{assign var="map_provider_api" value="`$map_provider`_map_api"}
{assign var="map_customer_templates" value="C"|fn_get_store_locator_map_templates}
{assign var="map_container" value="map_canvas"}

{if $store_locations}
    {if $map_customer_templates && $map_customer_templates.$map_provider}
        {include file=$map_customer_templates.$map_provider}
    {/if}

    <div style="padding-left: 420px">
        <div class="float-left" style="border: 1px solid #979797; width: 400px; height: 300px; margin-left: -420px" id="{$map_container}"></div>
        <div class="wysiwyg-content clearfix" id="stores_list_box">
            {foreach from=$store_locations item=loc key=num}
                <div class="product-container wysiwyg-content" id="loc_{$loc.store_location_id}">
                    <h1>{$loc.name}</h1>
                    {$loc.description nofilter}
                    {if $loc.city || $loc.country_title}{if $loc.city}{$loc.city}, {/if}{$loc.country_title}{/if}
                    <p>{include file="buttons/button.tpl" but_role="text" but_meta="cm-map-view-location" but_text=__("view_on_map") but_extra="data-ca-latitude={$loc.latitude} data-ca-longitude={$loc.longitude}"}</p>
                </div>   
            {if $store_locations|count > 1}   
                <hr />
            {/if}   
            {/foreach}

            {if $store_locations|count > 1}
                <h1>{__("all_stores")}</h1>
                <p>{include file="buttons/button.tpl" but_role="text" but_meta="cm-map-view-locations" but_text=__("view_on_map")}</p>
            {/if}
        </div>
    </div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{capture name="mainbox_title"}{__("store_locator")}{/capture}