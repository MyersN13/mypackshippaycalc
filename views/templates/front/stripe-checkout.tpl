<div class="stripe-checkout-container">
    <h3 class="page-subheading">{l s='Secure Payment' mod='easypostshipping'}</h3>
    
    {if $payment_methods}
    <div class="saved-methods">
        <h4>{l s='Saved Payment Methods' mod='easypostshipping'}</h4>
        {foreach $payment_methods as $method}
        <div class="payment-method">
            <input type="radio" name="payment_method" id="method_{$method->id}" checked>
            <label for="method_{$method->id}">
                <i class="fa fa-cc-{$method->card->brand}"></i>
                •••• {$method->card->last4}
                (Exp: {$method->card->exp_month}/{$method->card->exp_year})
            </label>
        </div>
        {/foreach}
    </div>
    {/if}

    <div id="card-element" class="mt-3"></div>
    <div id="card-errors" class="alert alert-danger mt-2" style="display:none"></div>
    
    <button id="submit-payment" class="btn btn-primary btn-block mt-3">
        {l s='Pay Now' mod='easypostshipping'}
    </button>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('{$stripe_pk}');
const elements = stripe.elements();
const card = elements.create('card');
card.mount('#card-element');

document.getElementById('submit-payment').addEventListener('click', async () => {
    const {error, paymentIntent} = await stripe.confirmCardPayment(
        '{$payment_intent}', {
            payment_method: {card}
        }
    );

    if (error) {
        document.getElementById('card-errors').textContent = error.message;
        document.getElementById('card-errors').style.display = 'block';
    } else {
        const response = await fetch('{$link->getModuleLink('easypostshipping', 'stripecheckout')}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'createOrder=1'
        });
        
        const result = await response.json();
        if (result.success) {
            window.location.href = result.redirect;
        }
    }
});
</script>

<style>
.stripe-checkout-container {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.payment-method {
    padding: 10px;
    border: 1px solid #eee;
    margin-bottom: 10px;
    border-radius: 4px;
}
</style>