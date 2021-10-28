<?php

namespace App\Actions;

use SeanKndy\SonarApi\Client;
use SeanKndy\SonarApi\Mutations\Inputs\InputBuilder;
use SeanKndy\SonarApi\Resources\Account;
use SeanKndy\SonarApi\Resources\AccountStatus;
use SeanKndy\SonarApi\Types\Int64Bit;

class UpdateAccountStatusAction
{
    private Client $sonarClient;

    public function __construct(Client $sonarClient)
    {
        $this->sonarClient = $sonarClient;
    }

    /**
     * @param Account|int $account
     * @throws \Exception
     */
    public function __invoke($account, AccountStatus $accountStatus): Account
    {
        $accountId = new Int64Bit($account instanceof Account ? $account->id : $account);

        return $this->sonarClient->mutations()->updateAccount([
            'id!' => $accountId,
            'input' => fn(InputBuilder $input): InputBuilder => $input->type('UpdateAccountMutationInput')->data([
                'accountStatusId' => new Int64Bit($accountStatus->id),
            ]),
        ])->return(Account::class)->run();
    }
}