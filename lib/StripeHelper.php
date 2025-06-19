<?php
class StripeHelper
{
    private static $initialized = false;

    public static function init()
    {
        if (!self::$initialized) {
            if (!class_exists('\Stripe\Stripe')) {
                require_once dirname(__FILE__).'/../vendor/autoload.php';
            }
            
            \Stripe\Stripe::setApiKey(Configuration::get('STRIPE_API_KEY'));
            self::$initialized = true;
        }
    }

    public static function createPaymentIntent($amount, $currency, $metadata = [])
    {
        self::init();
        
        return \Stripe\PaymentIntent::create([
            'amount' => (int)($amount * 100),
            'currency' => strtolower($currency),
            'setup_future_usage' => 'off_session',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => $metadata
        ]);
    }

    public static function getCustomerPaymentMethods($email)
    {
        self::init();
        
        try {
            $customer = \Stripe\Customer::all([
                'email' => $email,
                'limit' => 1
            ])->first();
            
            return $customer ? \Stripe\PaymentMethod::all([
                'customer' => $customer->id,
                'type' => 'card'
            ])->data : [];
        } catch (Exception $e) {
            return [];
        }
    }
}