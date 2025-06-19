<?php
class EasyPost
{
    // ... [keep existing properties and other methods]

    public function createShipment($data, $context)
    {
        // Remove tax passing to EasyPost - we'll handle it in PrestaShop
        unset($data['options']['invoice_amount']);
        
        // Just create the shipment without tax info
        return $this->request('shipments', $data, 'POST');
    }

    public function calculateShippingTax($cart, $shipping_cost)
    {
        try {
            $address = new Address($cart->id_address_delivery);
            
            // 1. Try Stripe Tax if enabled
            if (Configuration::get('EASYPOST_USE_STRIPE_TAX') && 
                Configuration::get('STRIPE_API_KEY')) {
                $stripe_tax = $this->calculateStripeTax(
                    $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS),
                    $shipping_cost,
                    $address->postcode,
                    Country::getIsoById($address->id_country),
                    State::getIsoById($address->id_state),
                    $context->currency->iso_code
                );
                
                if ($stripe_tax !== false) {
                    return $stripe_tax;
                }
            }
            
            // 2. Fallback to PrestaShop's native tax calculation
            $taxManager = TaxManagerFactory::getManager(
                $address,
                Product::getIdTaxRulesGroupMostUsed()
            );
            $taxCalculator = $taxManager->getTaxCalculator();
            
            return $taxCalculator->addTaxes($shipping_cost) - $shipping_cost;
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Tax calculation error: '.$e->getMessage(), 3);
            return 0;
        }
    }

    private function calculateStripeTax($product_total, $shipping_cost, $postal_code, $country_code, $state_code, $currency_code)
    {
        if (!class_exists('\Stripe\Stripe')) {
            return false;
        }

        try {
            \Stripe\Stripe::setApiKey(Configuration::get('STRIPE_API_KEY'));
            
            $tax_calculation = \Stripe\Tax\Calculation::create([
                'currency' => $currency_code,
                'customer_details' => [
                    'address' => [
                        'line1' => '',
                        'postal_code' => $postal_code,
                        'country' => $country_code,
                        'state' => $state_code,
                    ],
                    'address_source' => 'shipping',
                ],
                'line_items' => [
                    [
                        'amount' => (int)($product_total * 100),
                        'reference' => 'products',
                    ],
                    [
                        'amount' => (int)($shipping_cost * 100),
                        'reference' => 'shipping',
                    ],
                ],
            ]);
            
            // Find just the shipping tax portion
            foreach ($tax_calculation->line_items as $item) {
                if ($item->reference === 'shipping') {
                    return $item->amount_tax / 100;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe tax error: '.$e->getMessage(), 3);
            return false;
        }
    }

    // ... [keep other existing methods]
}