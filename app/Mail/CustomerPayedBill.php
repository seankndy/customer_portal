<?php

namespace App\Mail;

use App\DataTransferObjects\PaymentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerPayedBill extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public PaymentSubmission $paymentSubmission;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(PaymentSubmission $paymentSubmission)
    {
        $this->paymentSubmission = $paymentSubmission;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject($this->paymentSubmission->account->name . ' Payed Bill Online')
            ->view('emails.customer_payed_bill');
    }
}
