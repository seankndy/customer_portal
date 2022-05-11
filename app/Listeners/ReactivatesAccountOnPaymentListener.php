<?php

namespace App\Listeners;

use App\Actions\UpdateAccountStatusAction;
use App\Events\PaymentSuccessfullySubmittedEvent;
use App\Mail\CustomerPayedBill;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SeanKndy\SonarApi\Client;

class ReactivatesAccountOnPaymentListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Account status to set when an account is 'reactivated' after payment.
     * @var int|string  If name given rather than ID, then account status ID will be queried.
     */
    const REACTIVATION_STATUS = 1;

    /**
     * If the account is one of these statuses and the account pays up, reactivate to status above.
     */
    const REACTIVATES_ACCOUNT_STATUSES = [
        'Inactive / Collections',
        'On Hold',
    ];

    /**
     * If the account is one of these statuses and the account pays up, send notice to the alerts recipient
     * (config('mail.alerts_recipient'))
     */
    const SEND_NOTIFICATION_STATUSES = [
        'Inactive',
        'Inactive / Collections',
    ];

    private Client $sonarClient;

    private UpdateAccountStatusAction $updateAccountStatusAction;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        Client $sonarClient,
        UpdateAccountStatusAction $updateAccountStatusAction
    ) {
        $this->sonarClient = $sonarClient;
        $this->updateAccountStatusAction = $updateAccountStatusAction;
    }

    /**
     * Handle the event.
     *
     * @param  PaymentSuccessfullySubmittedEvent  $event
     * @return void
     */
    public function handle(PaymentSuccessfullySubmittedEvent $event)
    {
        $this->log($event, 'info', 'starting handling of payment submission');

        try {
            $delinquentInvoices = $this->sonarClient
                ->invoices()
                ->where('accountId', $event->paymentSubmission->account->id)
                ->where('delinquent', true)
                ->get();

            if ($delinquentInvoices->count() > 0) {
                $this->log($event, 'info', 'account has delinquent invoices, ending');

                // still delinquent, do not continue to reactivate
                return;
            }

            $currentAccountStatus = $this->sonarClient
                ->accountStatuses()
                ->whereHas('accounts', fn($search) => $search->where('id', $event->paymentSubmission->account->id))
                ->first();

            if (!$currentAccountStatus) {
                $this->log($event, 'info', 'failed to get the current account status from sonar');
            } else if (\in_array($currentAccountStatus->name, self::REACTIVATES_ACCOUNT_STATUSES)) {
                $newAccountStatus = is_int(self::REACTIVATION_STATUS)
                    ? self::REACTIVATION_STATUS
                    : $this->sonarClient
                        ->accountStatuses()
                        ->where('name', self::REACTIVATION_STATUS)
                        ->first();

                $this->log($event, 'info', 'account needs to be reactivated, setting status');

                ($this->updateAccountStatusAction)($event->paymentSubmission->account, $newAccountStatus);

                if (config('mail.alerts_recipient') && \in_array($currentAccountStatus->name, self::SEND_NOTIFICATION_STATUSES)) {
                    $this->log($event, 'info', 'sending customer payed bill notice to ' . config('mail.alerts_recipient'));
                    Mail::to(config('mail.alerts_recipient'))->send(new CustomerPayedBill($event->paymentSubmission));
                }
            } else {
                $this->log($event, 'info', 'account does not need reactivated; current_status=' . $currentAccountStatus->name);
            }
        } catch (\Exception $e) {
            $this->log($event, 'error', 'exception during handling: ' . $e->getMessage());
        }
    }

    private function log(PaymentSuccessfullySubmittedEvent $event, string $level, string $msg): void
    {
        Log::log($level, '[' . self::class . '] ' . $msg . '; account=' .
            $event->paymentSubmission->account->name . '; amount='. $event->paymentSubmission->amount);
    }
}
