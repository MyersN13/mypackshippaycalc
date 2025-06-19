<div class="box easypost-tracking">
    <h3>{l s='Shipping Information' mod='easypostshipping'}</h3>
    <p>
        {l s='Your order will be shipped via %s' mod='easypostshipping' sprintf=[$carrier_name]}<br>
        {l s='Tracking Number:' mod='easypostshipping'} <strong>{$tracking_number}</strong>
    </p>
    <a href="{$tracking_url}" class="btn btn-primary">
        <i class="icon-truck"></i> {l s='Track Your Package' mod='easypostshipping'}
    </a>
</div>