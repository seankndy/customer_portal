<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe;

class StripeController extends Controller
{

    protected $api_key;

    public function __construct()
    {
        $this->api_key = config("customer_portal.stripe_api_key");
    }

    /**
     * Return PaymentMethod associated with ID
     *
     * @param string $paymentMethodId
     * @return mixed
     */
    public function paymentMethod(Request $request, $paymentMethodId)
    {
        Stripe\Stripe::setApiKey($this->api_key);
        try {
            return Stripe\PaymentMethod::retrieve($paymentMethodId);
        } catch (Stripe\Exception\ApiErrorException $e)
        {
            return redirect()->back()->withErrors(utrans("errors.stripePaymentMethodNotFound"));
        }
    }

}
