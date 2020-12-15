<?php


namespace App\Billing;

use Stripe as RealStripe;


class Stripe
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
        $customer = RealStripe\Customer::create();

        $intent = RealStripe\SetupIntent::create([
            "customer" => $customer->id
        ]);

        return $intent->client_secret;
    }
}
