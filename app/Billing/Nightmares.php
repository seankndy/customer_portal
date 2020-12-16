<?php


namespace App\Billing;

use Stripe;


class Nightmares
{
    protected $api_key;

    public function __construct()
    {
        $this->api_key = config("customer_portal.stripe_api_key");
        Stripe\Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');
    }

    /**
     * Creates a Stripe customer and returns the customer object
     * 
     * @return Stripe\Customer
     */
    public function createCustomer()
    {
        return Stripe\Customer::create();
    }

    /**
     * Returns all saved payment methods for a customer id
     * 
     * @param string $customerId
     * 
     * @return mixed
     */
    public function listPaymentMethods($customerId)
    {
        return \Stripe\PaymentMethod::all([
            'customer' => $customerId,
            'type' => 'card',
        ]);
    }

    /**
     * Return a new Stripe\SetupIntent client secret
     * 
     * @param string $customerId 
     * 
     * @return string
     */
    public function setupIntent($customerId)
    {
        $intent = Stripe\SetupIntent::create([
            "customer" => $customerId
        ]);

        return $intent->client_secret;
    }
}
