document.addEventListener('DOMContentLoaded', function() {
    const stripe = Stripe(module_stripe_pk);
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');
    
    const paymentForm = document.getElementById('stripe-payment-form');
    const paymentMethods = document.getElementById('payment-methods');
    const useNewCard = document.getElementById('use-new-card');
    
    // Toggle between saved cards and new card
    useNewCard.addEventListener('change', function() {
        document.getElementById('card-element').style.display = this.checked ? 'block' : 'none';
    });
    
    paymentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitButton = document.getElementById('submit-payment');
        submitButton.disabled = true;
        
        try {
            // If using saved card
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (selectedMethod && !useNewCard.checked) {
                const response = await fetch(module_ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=confirm_order'
                });
                
                const result = await response.json();
                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    throw new Error(result.error || 'Payment failed');
                }
            } 
            // If using new card
            else {
                const {error, paymentIntent} = await stripe.confirmCardPayment(
                    paymentIntentClientSecret,
                    {
                        payment_method: {
                            card: cardElement,
                            billing_details: {
                                email: document.getElementById('email').value
                            }
                        }
                    }
                );
                
                if (error) {
                    throw error;
                }
                
                if (paymentIntent.status === 'succeeded') {
                    const response = await fetch(module_ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=confirm_order'
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = result.redirect;
                    }
                }
            }
        } catch (error) {
            showError(error.message);
            submitButton.disabled = false;
        }
    });
    
    function showError(message) {
        const errorElement = document.getElementById('card-errors');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
});