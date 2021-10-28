<?php

namespace App\DataTransferObjects;

use SeanKndy\SonarApi\Resources\Account;
use Spatie\DataTransferObject\DataTransferObject;

class PaymentSubmission extends DataTransferObject
{
    /**
     * Account payment being applied to.
     */
    public Account $account;

    /**
     * Amount (in cents) of payment.
     */
    public int $amount;
}