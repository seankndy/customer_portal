<?php


namespace App\Billing;

use Stripe;


class Nightmares
{
    protected $api_key;

    public function __construct()
    {
        $this->api_key = config("customer_portal.stripe_api_key");
    }

    /**
     * Return a new Stripe\SetupIntent client secret
     */
    public function setupIntent()
    {
        // Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');

        $customer = Stripe\Customer::create();

        $intent = Stripe\SetupIntent::create([
            "customer" => $customer->id
        ]);

        return $intent->client_secret;
    }
}
