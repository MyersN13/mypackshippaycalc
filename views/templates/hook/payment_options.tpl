<div class="stripe-payment-option">
    <div class="payment-option-icon">
        <i class="fa fa-lock"></i>
    </div>
    <div class="payment-option-content">
        <h5>{l s='Secure Stripe Payment' mod='easypostshipping'}</h5>
        <p>{l s='Pay with credit card or saved payment methods' mod='easypostshipping'}</p>
        <div class="card-images">
            <img src="{$module_dir}views/img/visa.png" alt="Visa" width="40">
            <img src="{$module_dir}views/img/mastercard.png" alt="Mastercard" width="40">
            <img src="{$module_dir}views/img/amex.png" alt="American Express" width="40">
        </div>
    </div>
</div>

<style>
.stripe-payment-option {
    display: flex;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
}
.payment-option-icon {
    font-size: 2rem;
    margin-right: 15px;
    color: #6772e5;
}
.payment-option-content h5 {
    margin: 0 0 5px 0;
}
.card-images img {
    margin-right: 5px;
}
</style>