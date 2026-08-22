<?php

namespace App\Services;

use App\Models\Invoice;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;

class InvoiceVerificationService
{
    public function url(Invoice $invoice): string
    {
        return route('public.invoices.show', $invoice->public_token);
    }

    public function qrCodeDataUri(Invoice $invoice, int $size = 180): string
    {
        $builder = new Builder(
            writer: new SvgWriter(),
            data: $this->url($invoice),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 10,
            foregroundColor: new Color(17, 24, 39),
            backgroundColor: new Color(255, 255, 255),
        );

        return $builder->build()->getDataUri();
    }
}
