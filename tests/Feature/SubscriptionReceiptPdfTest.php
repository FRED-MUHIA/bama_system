<?php

namespace Tests\Feature;

use App\Mail\SubscriptionReceiptMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class SubscriptionReceiptPdfTest extends TestCase
{
    public function test_receipt_renders_as_pdf_and_is_attached_to_email(): void
    {
        $receipt = [
            'number' => 'BAMA-RCT-10-20', 'invoice_number' => 'BAMA-10',
            'customer_name' => 'Example Client', 'plan_name' => 'Growth',
            'amount' => '5000.00', 'currency' => 'KES',
            'paid_at' => '20 Feb 2026 10:00', 'timezone' => 'Africa/Nairobi',
            'provider' => 'MPESA', 'reference' => 'TEST123',
            'renews_at' => '22 Mar 2026 10:00',
        ];
        $pdf = Pdf::loadView('pdf.subscription-receipt', compact('receipt'))->setPaper('a4')->output();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $mail = (new SubscriptionReceiptMail($receipt, $pdf))->build();
        $this->assertSame('BAMA-RCT-10-20.pdf', $mail->rawAttachments[0]['name']);
        $this->assertSame('application/pdf', $mail->rawAttachments[0]['options']['mime']);
        $this->assertStringContainsString('Next renewal: 22 Mar 2026 10:00', $mail->viewData['body']);
    }
}
