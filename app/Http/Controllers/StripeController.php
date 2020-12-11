<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe;

class StripeController extends Controller
{
    public function getPaymentIntent()
    {
        $customer = Stripe\Customer::create();
    }

    public function setApiKey()
    {

    }
}
