{foreach $payment_methods as $method}
<div class="payment-method card mb-2">
    <div class="card-body">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="saved_payment_method" 
                   id="method_{$method->id}" value="{$method->id}" checked>
            <label class="form-check-label" for="method_{$method->id}">
                <i class="fa fa-cc-{$method->card->brand}"></i> 
                •••• {$method->card->last4} 
                (Exp: {$method->card->exp_month}/{$method->card->exp_year})
            </label>
        </div>
    </div>
</div>
{/foreach}