<div class="easypost-tracking">
    <h2>{l s='Order Tracking' mod='easypostshipping'}</h2>
    
    <div class="panel">
        <div class="panel-heading">
            {l s='Shipment Information' mod='easypostshipping'}
        </div>
        <div class="panel-body">
            <dl class="dl-horizontal">
                <dt>{l s='Order Reference' mod='easypostshipping'}:</dt>
                <dd>{$order_reference}</dd>
                
                <dt>{l s='Tracking Number' mod='easypostshipping'}:</dt>
                <dd>{$tracking_code}</dd>
                
                <dt>{l s='Carrier' mod='easypostshipping'}:</dt>
                <dd>{$carrier}</dd>
                
                <dt>{l s='Status' mod='easypostshipping'}:</dt>
                <dd><strong>{$status}</strong></dd>
                
                {if $estimated_delivery}
                <dt>{l s='Estimated Delivery' mod='easypostshipping'}:</dt>
                <dd>{$estimated_delivery|date_format:"%b %e, %Y"}</dd>
                {/if}
            </dl>
            
            <a href="{$public_url}" class="btn btn-primary" target="_blank">
                {l s='View on Carrier Website' mod='easypostshipping'}
            </a>
        </div>
    </div>
    
    <div class="panel">
        <div class="panel-heading">
            {l s='Tracking History' mod='easypostshipping'}
        </div>
        <div class="panel-body">
            {if !empty($history)}
            <ul class="timeline">
                {foreach from=$history item=event}
                <li>
                    <div class="timeline-date">{$event.datetime|date_format:"%b %e, %Y %l:%M %p"}</div>
                    <div class="timeline-content">
                        <h4>{$event.message}</h4>
                        {if $event.tracking_location.city}
                        <p>
                            {$event.tracking_location.city}, 
                            {$event.tracking_location.state} 
                            {$event.tracking_location.zip} 
                            {$event.tracking_location.country}
                        </p>
                        {/if}
                    </div>
                </li>
                {/foreach}
            </ul>
            {else}
            <p class="text-muted">{l s='No tracking history available yet' mod='easypostshipping'}</p>
            {/if}
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 50px;
    list-style: none;
}
.timeline:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
}
.timeline li {
    position: relative;
    margin-bottom: 20px;
}
.timeline-date {
    position: absolute;
    left: -50px;
    width: 40px;
    text-align: right;
    font-size: 12px;
    color: #999;
}
.timeline-content {
    padding: 10px 15px;
    background: #f9f9f9;
    border-radius: 4px;
    border: 1px solid #eee;
}
.timeline-content h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
}
</style>