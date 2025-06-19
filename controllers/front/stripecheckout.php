<?php
class EasyPostShippingStripeCheckoutModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        if (!$this->checkAuthorization()) {
            Tools::redirect('index.php?controller=order');
        }

        $this->createPaymentIntent();
        $this->displayCheckout();
    }

    private function checkAuthorization()
    {
        return $this->context->cart->id_customer &&
               $this->context->cart->id_address_delivery &&
               $this->context->cart->id_address_invoice;
    }

    private function createPaymentIntent()
    {
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);
        
        $paymentIntent = StripeHelper::createPaymentIntent(
            $cart->getOrderTotal(true, Cart::BOTH),
            $currency->iso_code,
            ['cart_id' => $cart->id]
        );

        $this->context->smarty->assign([
            'stripe_pk' => Configuration::get('STRIPE_PUBLISHABLE_KEY'),
            'payment_intent' => $paymentIntent->client_secret,
            'payment_methods' => StripeHelper::getCustomerPaymentMethods($this->context->customer->email),
            'module_dir' => $this->module->getPathUri()
        ]);
    }

    private function displayCheckout()
    {
        $this->setTemplate('module:easypostshipping/views/templates/front/stripe-checkout.tpl');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('createOrder')) {
            $this->module->validateOrder(
                $this->context->cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                $this->context->cart->getOrderTotal(true),
                'Stripe',
                null,
                [],
                $this->context->currency->id
            );
            
            die(json_encode([
                'success' => true,
                'redirect' => $this->context->link->getPageLink(
                    'order-confirmation',
                    true,
                    null,
                    [
                        'id_cart' => $this->context->cart->id,
                        'id_module' => $this->module->id,
                        'id_order' => $this->module->currentOrder
                    ]
                )
            ]));
        }
    }
}