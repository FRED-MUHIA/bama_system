<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\Signatory;
use App\Models\Site;
use App\Models\TemplateCategory;
use App\Models\Warranty;
use App\Support\ActiveBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LetterService
{
    public function number(string $prefix = 'LTR'): string
    {
        $prefix = strtoupper(Str::slug($prefix, '')) ?: 'LTR';
        $year = now()->format('Y');
        $base = "{$prefix}-{$year}-";

        $businessId = ActiveBusiness::id();
        if ($businessId) {
            DB::table('businesses')->where('id', $businessId)->lockForUpdate()->first();
        }

        $highest = Letter::query()
            ->where('letter_number', 'like', $base.'%')
            ->lockForUpdate()
            ->pluck('letter_number')
            ->reduce(function (int $highest, string $number) use ($base) {
                if (! preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $number, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        return sprintf('%s%s', $base, str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT));
    }

    public function render(string $content, array $context): string
    {
        $client = $context['client'] ?? null;
        $site = $context['site'] ?? null;
        $project = $context['project'] ?? null;
        $invoice = $context['invoice'] ?? null;
        $receipt = $context['receipt'] ?? null;
        $payment = $context['payment'] ?? null;
        $letter = $context['letter'] ?? null;
        $businessId = $letter?->business_id
            ?? $invoice?->business_id
            ?? $receipt?->business_id
            ?? $project?->business_id
            ?? $client?->business_id
            ?? ActiveBusiness::id();
        $company = $this->companySettingsForBusiness($businessId);
        $signatory = $this->defaultSignatoryForBusiness($businessId);

        $companyName = $company?->company_name ?: 'Bama';
        $contactPerson = $client?->primaryContact;

        return strtr($content, [
            '{{date}}' => now()->format('d M Y'),
            '{{today_date}}' => now()->format('d M Y'),
            '{{client}}' => $client?->name ?? '',
            '{{client_name}}' => $client?->name ?? '',
            '{{client_company}}' => $client?->company_name ?? '',
            '{{contact}}' => $contactPerson?->full_name ?? '',
            '{{contact_person}}' => $contactPerson?->full_name ?? '',
            '{{site}}' => $site?->site_name ?? '',
            '{{site_name}}' => $site?->site_name ?? '',
            '{{site_address}}' => $site?->address ?? '',
            '{{project}}' => $project?->project_name ?? '',
            '{{project_name}}' => $project?->project_name ?? '',
            '{{project_scope}}' => $project?->scope ?? '',
            '{{invoice}}' => $invoice?->invoice_number ?? '',
            '{{invoice_number}}' => $invoice?->invoice_number ?? '',
            '{{invoice_total}}' => $invoice ? number_format((float) $invoice->total, 2) : '',
            '{{invoice_balance}}' => $invoice ? number_format((float) $invoice->balance, 2) : '',
            '{{balance}}' => $invoice ? number_format((float) $invoice->balance, 2) : '',
            '{{receipt_number}}' => $receipt?->receipt_number ?? '',
            '{{payment}}' => $payment ? number_format((float) $payment->amount, 2) : ($receipt ? number_format((float) $receipt->amount_paid, 2) : ''),
            '{{company}}' => $companyName,
            '{{company_name}}' => $companyName,
            '{{company_address}}' => $company?->address ?? '',
            '{{company_phone}}' => $company?->phone ?? '',
            '{{company_email}}' => $company?->email ?? '',
            '{{company_logo}}' => $company?->logoUrl() ? '<img src="'.$company->logoUrl().'" style="max-height:80px;">' : '',
            '{{prepared_by}}' => $signatory?->name ?? '',
            '{{designation}}' => $signatory?->title ?? '',
            '{{signature}}' => $this->signatureHtml($signatory),
            '{{letter_number}}' => $letter?->letter_number ?? '',
            '{{qr_code}}' => $this->qrCodeHtml($letter, $companyName),
        ]);
    }

    public function signatureHtml(?Signatory $signatory): string
    {
        if (! $signatory) {
            return '';
        }

        $sigUrl = $signatory->signatureUrl();
        if ($sigUrl) {
            return '<img src="'.$sigUrl.'" style="max-height:60px; margin-bottom:4px;"><br>';
        }

        return '';
    }

    public function qrCodeHtml(?Letter $letter, string $companyName): string
    {
        if (! $letter || ! $letter->exists) {
            return '';
        }

        try {
            $url = route('public.letters.verify', $letter->id);
            $builder = new Builder(
                writer: new SvgWriter,
                data: $url,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 100,
                margin: 5,
                foregroundColor: new Color(17, 24, 39),
                backgroundColor: new Color(255, 255, 255),
            );

            $dataUri = $builder->build()->getDataUri();

            return '<img src="'.$dataUri.'" style="width:100px;height:100px;display:inline-block;" alt="QR Code">';
        } catch (\Throwable) {
            return '';
        }
    }

    public function qrCodeDataUri(Letter $letter, int $size = 100): string
    {
        try {
            $url = route('public.letters.verify', $letter->id);
            $builder = new Builder(
                writer: new SvgWriter,
                data: $url,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: $size,
                margin: 5,
                foregroundColor: new Color(17, 24, 39),
                backgroundColor: new Color(255, 255, 255),
            );

            return $builder->build()->getDataUri();
        } catch (\Throwable) {
            return '';
        }
    }

    public function recordVersion(Letter $letter): void
    {
        $letter->versions()->create([
            'version' => ((int) $letter->versions()->max('version')) + 1,
            'subject' => $letter->subject,
            'content' => $letter->content,
            'status' => $letter->status,
            'created_by' => auth()->id(),
        ]);
    }

    public function ensureDefaultTemplates(): void
    {
        if (! ActiveBusiness::id()) {
            return;
        }

        $categoryIds = $this->templateCategoryIds();

        foreach ($this->defaults() as $template) {
            if (LetterTemplate::where('name', $template['name'])->exists()) {
                continue;
            }

            $payload = $template + ['is_active' => true, 'output_format' => 'PDF'];

            if (Schema::hasColumn('letter_templates', 'is_system')) {
                $payload['is_system'] = true;
            }

            if (Schema::hasColumn('letter_templates', 'template_category_id')) {
                $payload['template_category_id'] = $categoryIds[$template['type']] ?? null;
            }

            LetterTemplate::create($payload);
        }
    }

    public function sourceContext(array $data): array
    {
        $invoice = ! empty($data['invoice_id']) ? Invoice::with('client.primaryContact', 'site', 'project', 'payments')->find($data['invoice_id']) : null;
        $receipt = ! empty($data['receipt_id']) ? Receipt::with('invoice.client.primaryContact', 'invoice.site', 'invoice.project', 'payment')->find($data['receipt_id']) : null;
        $project = ! empty($data['project_id']) ? Project::with('client.primaryContact', 'site')->find($data['project_id']) : null;
        $warranty = ! empty($data['warranty_id']) ? Warranty::with('client.primaryContact', 'site', 'project')->find($data['warranty_id']) : null;
        $client = ! empty($data['client_id']) ? Client::with('primaryContact')->find($data['client_id']) : null;

        $client ??= $invoice?->client ?? $receipt?->invoice?->client ?? $project?->client ?? $warranty?->client;
        $site = ! empty($data['site_id']) ? Site::find($data['site_id']) : ($invoice?->site ?? $receipt?->invoice?->site ?? $project?->site ?? $warranty?->site);
        $payment = ! empty($data['payment_id']) ? Payment::find($data['payment_id']) : $receipt?->payment;

        return compact('client', 'site', 'project', 'invoice', 'receipt', 'payment', 'warranty');
    }

    public function setLetterContext(Letter $letter): array
    {
        $letter->load('client', 'site', 'project', 'invoice', 'receipt');
        $context = $this->sourceContext([
            'client_id' => $letter->client_id,
            'site_id' => $letter->site_id,
            'project_id' => $letter->project_id,
            'invoice_id' => $letter->invoice_id,
            'receipt_id' => $letter->receipt_id,
            'payment_id' => $letter->payment_id,
            'warranty_id' => $letter->warranty_id,
        ]);
        $context['letter'] = $letter;

        return $context;
    }

    public function writeDocx(Letter $letter): string
    {
        $path = tempnam(sys_get_temp_dir(), 'letter_docx_');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $body = collect([$letter->letter_number, $letter->subject, '', ...preg_split("/\r\n|\n|\r/", $letter->content)])
            ->map(fn ($line) => '<w:p><w:r><w:t>'.htmlspecialchars($line ?? '', ENT_XML1).'</w:t></w:r></w:p>')
            ->implode('');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>');
        $zip->close();

        return $path;
    }

    public function pdf(Letter $letter)
    {
        $letter->load('client', 'site', 'project', 'invoice', 'receipt');

        $company = $this->companySettingsForBusiness($letter->business_id);
        $signatory = $this->defaultSignatoryForBusiness($letter->business_id);
        $qrCode = $this->qrCodeDataUri($letter, 100);

        $context = $this->setLetterContext($letter);

        $renderedContent = $this->render($letter->content, $context);

        return Pdf::loadView('pdf.letter', [
            'letter' => $letter,
            'settings' => $company,
            'signatory' => $signatory,
            'qrCode' => $qrCode,
            'renderedContent' => $renderedContent,
            'isRendered' => $letter->content_type === 'html',
        ]);
    }

    private function defaults(): array
    {
        return [
            // FINANCIAL
            ['name' => 'Payment Request', 'type' => 'Financial', 'default_subject' => 'Payment Request – {{invoice_number}}', 'content' => "Dear {{client_name}},\n\nRE: PAYMENT REQUEST – {{invoice_number}}\n\nWe kindly request payment for the above-referenced invoice. Below are the details:\n\nInvoice Number: {{invoice_number}}\nInvoice Total: {{invoice_total}}\nOutstanding Balance: {{invoice_balance}}\n\nWe would appreciate prompt processing of this payment. Please do not hesitate to contact us should you have any queries.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Payment Reminder', 'type' => 'Financial', 'default_subject' => 'Payment Reminder – {{invoice_number}}', 'content' => "Dear {{client_name}},\n\nRE: PAYMENT REMINDER – {{invoice_number}}\n\nWe wish to bring to your attention that invoice {{invoice_number}} for {{invoice_total}} is now due for payment.\n\nOutstanding Balance: {{invoice_balance}}\n\nWe kindly request that you arrange payment at your earliest convenience to avoid any interruption of services.\n\nShould you have any questions regarding this invoice, please contact us.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Acknowledgement of Payment', 'type' => 'Financial', 'default_subject' => 'Acknowledgement of Payment – {{receipt_number}}', 'content' => "Dear {{client_name}},\n\nRE: ACKNOWLEDGEMENT OF PAYMENT\n\nWe acknowledge with thanks receipt of your payment of {{payment}} towards invoice {{invoice_number}}.\n\nReceipt Number: {{receipt_number}}\n\nThis serves as our official acknowledgement. Please retain this letter for your records.\n\nThank you for your continued partnership.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Outstanding Balance Notice', 'type' => 'Financial', 'default_subject' => 'Outstanding Balance Notice – {{invoice_number}}', 'content' => "Dear {{client_name}},\n\nRE: OUTSTANDING BALANCE NOTICE\n\nOur records indicate that invoice {{invoice_number}} dated {{date}} has an outstanding balance of {{invoice_balance}}.\n\nWe kindly request that this amount be settled within 7 days from the date of this letter. Failure to do so may result in temporary suspension of services.\n\nIf you have already made payment, please disregard this notice.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Final Demand Letter', 'type' => 'Financial', 'default_subject' => 'Final Demand – {{invoice_number}}', 'content' => "Dear {{client_name}},\n\nRE: FINAL DEMAND – {{invoice_number}}\n\nDespite previous reminders, the amount of {{invoice_balance}} on invoice {{invoice_number}} remains unpaid.\n\nThis is our final demand for payment. Unless the full amount is received within 7 days, we shall have no alternative but to take legal action to recover the debt without further notice.\n\nAll costs associated with recovery will be for your account.\n\nYours faithfully,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Refund Letter', 'type' => 'Financial', 'default_subject' => 'Refund Advice – {{invoice_number}}', 'content' => "Dear {{client_name}},\n\nRE: REFUND ADVICE\n\nWe are pleased to advise that a refund of {{payment}} has been processed in your favour.\n\nReference: {{receipt_number}}\nInvoice: {{invoice_number}}\n\nThe refund will reflect in your account within 3–5 business days.\n\nWe apologise for any inconvenience caused.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            // PROJECTS
            ['name' => 'Project Proposal', 'type' => 'Project', 'default_subject' => 'Project Proposal – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: PROJECT PROPOSAL – {{project_name}}\n\nWe are pleased to submit our proposal for {{project_name}} at {{site_name}}.\n\nProject Scope:\n{{project_scope}}\n\nWe believe our approach delivers the best value and quality for this project. We look forward to your favourable response.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Project Handover', 'type' => 'Project', 'default_subject' => 'Project Handover – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: PROJECT HANDOVER – {{project_name}}\n\nWe are pleased to formally hand over {{project_name}} at {{site_name}}.\n\nThis letter confirms that the project has been completed to the agreed specifications. All necessary documentation, training, and warranty information have been provided.\n\nWe thank you for the opportunity to serve you and look forward to future collaboration.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Project Completion', 'type' => 'Project', 'default_subject' => 'Project Completion – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: PROJECT COMPLETION – {{project_name}}\n\nWe confirm the completion of {{project_name}} at {{site_name}} on {{today_date}}.\n\nThe project has been executed in accordance with the agreed scope and specifications. All milestones have been achieved.\n\nPlease review and provide your sign-off. Should you require any further assistance, do not hesitate to contact us.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Site Visit Report', 'type' => 'Project', 'default_subject' => 'Site Visit Report – {{site_name}}', 'content' => "Dear {{client_name}},\n\nRE: SITE VISIT REPORT – {{site_name}}\n\nThis letter serves as a report of our site visit on {{today_date}} regarding {{project_name}}.\n\nObservations and recommendations have been documented. Please review and advise on the way forward.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Progress Update', 'type' => 'Project', 'default_subject' => 'Progress Update – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: PROGRESS UPDATE – {{project_name}}\n\nWe are pleased to provide the following progress update on {{project_name}} at {{site_name}}.\n\nAll works are proceeding according to the project schedule. We will continue to keep you informed of further developments.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Mobilization Letter', 'type' => 'Project', 'default_subject' => 'Mobilization Notice – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: MOBILIZATION NOTICE – {{project_name}}\n\nWe wish to inform you that we will be mobilizing to {{site_name}} on {{today_date}} to commence {{project_name}}.\n\nOur team will coordinate with your site representative for access and logistics.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            // LEGAL & CONTRACTS
            ['name' => 'Variation Order', 'type' => 'Legal', 'default_subject' => 'Variation Order – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: VARIATION ORDER – {{project_name}}\n\nWe write to inform you of a variation to the scope of works for {{project_name}} at {{site_name}}.\n\nDetails of the variation and associated costs are outlined in the attached document. Please review and confirm your approval.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Extension Request', 'type' => 'Legal', 'default_subject' => 'Extension Request – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: EXTENSION REQUEST – {{project_name}}\n\nWe request an extension of time for {{project_name}} due to circumstances beyond our control.\n\nThe revised completion date and revised schedule are attached for your review.\n\nWe appreciate your understanding.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Contract Cover Letter', 'type' => 'Legal', 'default_subject' => 'Contract Cover Letter – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: CONTRACT COVER LETTER – {{project_name}}\n\nPlease find attached the contract documents for {{project_name}} at {{site_name}} for your review and execution.\n\nKindly sign and return one copy to us for our records.\n\nWe look forward to a successful engagement.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Formal Notice', 'type' => 'Legal', 'default_subject' => 'Formal Notice – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: FORMAL NOTICE\n\nPlease take this letter as formal notice regarding {{project_name}} at {{site_name}}.\n\nDetails of this notice are outlined below:\n\nYours faithfully,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            // WARRANTY & SUPPORT
            ['name' => 'Warranty Confirmation', 'type' => 'Warranty', 'default_subject' => 'Warranty Confirmation – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: WARRANTY CONFIRMATION – {{project_name}}\n\nThis letter confirms warranty coverage for works completed under {{project_name}} at {{site_name}}.\n\nThe warranty covers defects in materials and workmanship as per our agreement.\n\nPlease contact us should you need to make a claim.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Warranty Claim Support', 'type' => 'Warranty', 'default_subject' => 'Warranty Claim Support – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: WARRANTY CLAIM SUPPORT – {{project_name}}\n\nWe acknowledge receipt of your warranty claim regarding {{project_name}} at {{site_name}}.\n\nOur technical team will schedule a site visit to assess and address the issue promptly.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Maintenance Notice', 'type' => 'Warranty', 'default_subject' => 'Scheduled Maintenance Notice', 'content' => "Dear {{client_name}},\n\nRE: SCHEDULED MAINTENANCE NOTICE\n\nWe wish to inform you of scheduled maintenance for {{site_name}} on {{today_date}}.\n\nOur team will be on-site to perform routine maintenance as per our service agreement.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Service Completion', 'type' => 'Warranty', 'default_subject' => 'Service Completion – {{project_name}}', 'content' => "Dear {{client_name}},\n\nRE: SERVICE COMPLETION – {{project_name}}\n\nWe confirm completion of service works at {{site_name}} under {{project_name}}.\n\nAll maintenance and repair works have been carried out to the required standards.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            // GENERAL BUSINESS
            ['name' => 'General Letter', 'type' => 'General', 'default_subject' => 'General Correspondence', 'content' => "Dear {{client_name}},\n\nRE: GENERAL CORRESPONDENCE\n\nWe write to you regarding {{project_name}} at {{company_name}}.\n\nPlease do not hesitate to contact us should you require any further information.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Appreciation Letter', 'type' => 'General', 'default_subject' => 'Appreciation', 'content' => "Dear {{client_name}},\n\nRE: APPRECIATION\n\nWe wish to express our sincere appreciation for your continued partnership and trust in {{company_name}}.\n\nIt has been a pleasure working with you on {{project_name}}. We look forward to many more successful collaborations.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Meeting Request', 'type' => 'General', 'default_subject' => 'Meeting Request', 'content' => "Dear {{client_name}},\n\nRE: MEETING REQUEST\n\nWe would like to schedule a meeting to discuss {{project_name}}.\n\nKindly advise on your availability so we can arrange a suitable time.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Recommendation Letter', 'type' => 'General', 'default_subject' => 'Letter of Recommendation', 'content' => "To Whom It May Concern,\n\nRE: LETTER OF RECOMMENDATION\n\nWe are pleased to recommend {{client_name}} of {{client_company}}.\n\nWe have had the privilege of working with them on {{project_name}} and found them to be professional, reliable, and competent.\n\nPlease do not hesitate to contact us should you require further information.\n\nYours faithfully,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Introduction Letter', 'type' => 'General', 'default_subject' => 'Introduction – {{company_name}}', 'content' => "Dear {{client_name}},\n\nRE: INTRODUCTION\n\nWe are pleased to introduce {{company_name}} – your trusted partner in technology solutions.\n\nWe offer a comprehensive range of services including:\n- Technology Infrastructure\n- Security Systems\n- Structured Cabling\n- Electrical Works\n- Maintenance & Support\n\nWe look forward to serving you.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Apology Letter', 'type' => 'General', 'default_subject' => 'Apology', 'content' => "Dear {{client_name}},\n\nRE: APOLOGY\n\nWe sincerely apologise for the inconvenience caused regarding {{project_name}} at {{site_name}}.\n\nWe assure you that we have taken steps to prevent a recurrence. Your satisfaction is our priority.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            // PROCUREMENT
            ['name' => 'RFQ Cover Letter', 'type' => 'Procurement', 'default_subject' => 'Request for Quotation', 'content' => "Dear Supplier,\n\nRE: REQUEST FOR QUOTATION\n\n{{company_name}} invites you to submit a quotation for the requirements outlined in the attached document.\n\nPlease submit your quotation by {{today_date}}.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Supplier Communication', 'type' => 'Procurement', 'default_subject' => 'Supplier Communication', 'content' => "Dear Supplier,\n\nRE: SUPPLIER COMMUNICATION\n\nWe write to you regarding the supply of goods and services for {{project_name}}.\n\nKindly find attached the relevant documentation for your reference.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Delivery Confirmation', 'type' => 'Procurement', 'default_subject' => 'Delivery Confirmation', 'content' => "Dear {{client_name}},\n\nRE: DELIVERY CONFIRMATION\n\nWe confirm delivery of goods and services for {{project_name}} at {{site_name}} on {{today_date}}.\n\nPlease inspect and confirm receipt.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
            ['name' => 'Purchase Request', 'type' => 'Procurement', 'default_subject' => 'Purchase Request', 'content' => "Dear {{client_name}},\n\nRE: PURCHASE REQUEST\n\nWe request approval to procure the items required for {{project_name}}.\n\nDetails of the requested items are attached for your review.\n\nYours sincerely,\n\n{{prepared_by}}\n{{designation}}\n{{company_name}}"],
        ];
    }

    private function templateCategoryIds(): array
    {
        if (! Schema::hasTable('template_categories')) {
            return [];
        }

        $slugsByType = [
            'Financial' => 'financial',
            'Project' => 'projects',
            'Legal' => 'legal-contracts',
            'Warranty' => 'warranty-support',
            'General' => 'general-business',
            'Procurement' => 'procurement',
        ];

        $categories = TemplateCategory::whereIn('slug', array_values($slugsByType))
            ->pluck('id', 'slug');

        return collect($slugsByType)
            ->mapWithKeys(fn (string $slug, string $type) => [$type => $categories[$slug] ?? null])
            ->all();
    }

    private function companySettingsForBusiness(?int $businessId): ?CompanySetting
    {
        if (! $businessId) {
            return CompanySetting::first();
        }

        return CompanySetting::withoutGlobalScope('business')->firstOrCreate(
            ['business_id' => $businessId],
            $this->defaultCompanySettings()
        );
    }

    private function defaultCompanySettings(): array
    {
        $defaults = ['company_name' => ActiveBusiness::current()?->name ?? 'Bama'];

        foreach ([
            'primary_color' => CompanySetting::DEFAULT_PRIMARY_COLOR,
            'secondary_color' => CompanySetting::DEFAULT_SECONDARY_COLOR,
            'accent_color' => CompanySetting::DEFAULT_ACCENT_COLOR,
        ] as $column => $color) {
            if (Schema::hasColumn('company_settings', $column)) {
                $defaults[$column] = $color;
            }
        }

        return $defaults;
    }

    private function defaultSignatoryForBusiness(?int $businessId): ?Signatory
    {
        if (! Schema::hasTable('signatories')) {
            return null;
        }

        $query = $businessId
            ? Signatory::withoutGlobalScope('business')->where('business_id', $businessId)
            : Signatory::query();

        return $query->where('is_default', true)->where('is_active', true)->first();
    }
}
