<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use SonarSoftware\CustomerPortalFramework\Controllers\AccountBillingController;

trait ListsPaymentMethods
{
    /**
     * Get all the payment method options
     * @return mixed
     */
    private function getPaymentMethods()
    {
        $accountBillingController = new AccountBillingController();
        if (!Cache::tags("billing.payment_methods")->has(get_user()->accountId)) {
            $validAccountMethods = $accountBillingController->getValidPaymentMethods(get_user()->accountId);
            Cache::tags("billing.payment_methods")->put(get_user()->accountId, $validAccountMethods, 10*60);
        }
        return Cache::tags("billing.payment_methods")->get(get_user()->accountId);
    }
}
