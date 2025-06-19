<div class="easypost-rates-container">
    {if $rates}
    <div class="delivery-options">
        {foreach from=$rates item=rate}
        <div class="delivery-option">
            <div class="row">
                <div class="col-sm-1">
                    <input type="radio" name="delivery_option" value="{$rate.id}" 
                           id="delivery_option_{$rate.id}" 
                           {if $rate@first}checked{/if}>
                </div>
                <div class="col-sm-5">
                    <label for="delivery_option_{$rate.id}">
                        <strong>{$rate.carrier}</strong> - {$rate.service}
                        {if $rate.delivery_days}
                        <br><small>{l s='Est. delivery: %d days' mod='easypostshipping' sprintf=[$rate.delivery_days]}</small>
                        {/if}
                    </label>
                </div>
                <div class="col-sm-3">
                    {Tools::displayPrice($rate.rate)}
                </div>
                {if $tax_enabled && !$tax_included}
                <div class="col-sm-2">
                    +{Tools::displayPrice($rate.tax_amount)} {l s='tax' mod='easypostshipping'}
                </div>
                {/if}
                <div class="col-sm-3 text-right">
                    <strong>{Tools::displayPrice($rate.total_with_tax)}</strong>
                </div>
            </div>
        </div>
        {/foreach}
    </div>
    {else}
    <div class="alert alert-warning">
        {l s='No shipping methods available for your address' mod='easypostshipping'}
    </div>
    {/if}
</div>