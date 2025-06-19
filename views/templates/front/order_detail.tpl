<div class="box easypost-tracking-info">
    <h3>{l s='Shipping Information' mod='easypostshipping'}</h3>
    <p>
        {l s='Tracking Number' mod='easypostshipping'}: 
        <strong>{$tracking_code}</strong>
    </p>
    <a href="{$tracking_url}" class="btn btn-primary">
        {l s='Track Your Package' mod='easypostshipping'}
    </a>
</div>