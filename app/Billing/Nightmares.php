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
     *
     * @return string
     */
    public function setupIntent()
    {
        Stripe\Stripe::setApiKey($this->api_key);

        $customer = Stripe\Customer::create();

        $intent = Stripe\SetupIntent::create([
            "customer" => $customer->id
        ]);

        return $intent->client_secret;
    }
}
