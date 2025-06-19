<div class="panel">
    <div class="panel-heading">
        <i class="icon-print"></i> {l s='Print Shipping Documents' mod='easypostshipping'}
    </div>
    <div class="panel-body">
        <form method="post" action="{$smarty.server.REQUEST_URI}">
            <div class="form-group">
                <label>{l s='Select Documents to Print' mod='easypostshipping'}</label>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="documents[]" value="label" checked> 
                        {l s='Shipping Labels' mod='easypostshipping'}
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="documents[]" value="invoice" checked> 
                        {l s='Invoices' mod='easypostshipping'}
                    </label>
                </div>
            </div>
            <button type="submit" name="submitBulkPrint" class="btn btn-primary">
                <i class="icon-print"></i> {l s='Print Selected' mod='easypostshipping'}
            </button>
        </form>
    </div>
</div>