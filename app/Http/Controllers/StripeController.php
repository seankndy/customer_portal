<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe;

class StripeController extends Controller
{
    private $api_key;

    public function __construct()
    {
        $this->api_key = config('customer_portal.stripe_api_key');
    }
    public function getPaymentIntent()
    {
        $customer = Stripe\Customer::create();
    }

    public function setApiKey()
    {

    }
}
