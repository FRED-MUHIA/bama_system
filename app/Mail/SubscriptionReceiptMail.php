<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class SubscriptionReceiptMail extends Mailable
{
    public function __construct(public array $receipt, private string $pdf) {}

    public function build(): static
    {
        $subject = 'Bama subscription payment received — receipt '.$this->receipt['number'];
        $body = 'Thank you for your subscription payment. Your receipt is attached as a PDF.'
            ."\n\nReceipt: ".$this->receipt['number']
            ."\nInvoice: ".$this->receipt['invoice_number']
            ."\nPackage: ".$this->receipt['plan_name']
            ."\nAmount paid: ".$this->receipt['currency'].' '.number_format((float) $this->receipt['amount'], 2)
            ."\nPaid at: ".$this->receipt['paid_at'].' '.$this->receipt['timezone']
            .($this->receipt['renews_at'] ? "\nNext renewal: ".$this->receipt['renews_at'].' '.$this->receipt['timezone'] : '');

        return $this->subject($subject)
            ->view('emails.bama-system')->text('emails.bama-text')
            ->with([
                'appName' => config('mail.brand.name', 'Bama'), 'subject' => $subject,
                'headline' => 'Your subscription payment receipt', 'body' => $body,
                'preheader' => 'Your payment receipt is attached.', 'actionUrl' => null, 'footerNote' => null,
            ])
            ->attachData($this->pdf, $this->receipt['number'].'.pdf', ['mime' => 'application/pdf']);
    }
}
