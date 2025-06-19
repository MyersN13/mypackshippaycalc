<div class="panel easypost-panel">
    <div class="panel-heading">
        <i class="icon-truck"></i> {l s='EasyPost Shipping' mod='easypostshipping'}
    </div>
    {if $shipment}
    <div class="alert alert-success">
        <p>{l s='Shipment created' mod='easypostshipping'}: {$shipment.tracking_code}</p>
        <a href="{$link->getAdminLink('AdminEasyPostSettings')}&action=printLabel&id_shipment={$shipment.id_shipment}" 
           class="btn btn-primary" target="_blank">
            <i class="icon-print"></i> {l s='Print Label' mod='easypostshipping'}
        </a>
        <a href="{$link->getAdminLink('AdminEasyPostRMAController')}&id_order={$order->id}" 
           class="btn btn-default">
            <i class="icon-refresh"></i> {l s='Create RMA' mod='easypostshipping'}
        </a>
    </div>
    {else}
    <div class="alert alert-info">
        <p>{l s='No shipment created yet' mod='easypostshipping'}</p>
    </div>
    {/if}
</div>