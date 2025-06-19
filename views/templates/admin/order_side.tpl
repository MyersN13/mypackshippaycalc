<div class="panel easypost-panel">
    <div class="panel-heading">
        <i class="icon-info-circle"></i> {l s='Shipping Actions' mod='easypostshipping'}
    </div>
    <div class="panel-body">
        <a href="{$link->getAdminLink('AdminEasyPostBulkPrint')}&id_order={$order->id}" 
           class="btn btn-default btn-block">
            <i class="icon-print"></i> {l s='Print Documents' mod='easypostshipping'}
        </a>
    </div>
</div>