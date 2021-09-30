<?php

namespace App\DataTransferObjects;

use Illuminate\Http\Request;
use Spatie\DataTransferObject\DataTransferObject;

class AccountTicketData extends DataTransferObject
{
    public string $subject;

    public int $accountId;

    public static function fromRequest(Request $request)
    {
        return new self([
            'subject' => $request->input('subject'),
            'accountId' => (int)$request->input('account_id', get_user()->accountId),
        ]);
    }
}