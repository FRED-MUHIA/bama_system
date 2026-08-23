<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\DocumentMedia;
use App\Models\Invoice;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\Signatory;
use App\Models\Site;
use App\Models\Warranty;
use App\Services\LetterService;
use App\Services\OutgoingMailService;
use App\Support\ActiveBusiness;
use App\Support\PublicUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LetterController extends Controller
{
    public function __construct(private LetterService $letters, private OutgoingMailService $outgoingMail) {}

    public function index(Request $request)
    {
        $this->requireModule();
        $this->letters->ensureDefaultTemplates();

        $query = Letter::with('client', 'project', 'invoice', 'receipt')->latest();
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('letter_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('project', fn ($project) => $project->where('project_name', 'like', "%{$search}%"))
                    ->orWhereHas('invoice', fn ($invoice) => $invoice->where('invoice_number', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date('date'));
        }

        return view('letters.index', [
            'letters' => $query->paginate(12)->withQueryString(),
            'templates' => LetterTemplate::latest()->paginate(10, ['*'], 'templates'),
            'types' => Letter::TYPES,
            'statuses' => Letter::STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        $this->requireModule();
        $this->letters->ensureDefaultTemplates();

        $data = $this->sourceData($request);
        $template = $this->templateFor($request->input('template'), $request->input('template_id'));
        $context = $this->letters->sourceContext($data);
        $letter = new Letter($data + [
            'prefix' => $request->input('prefix', 'LTR'),
            'type' => $template?->type ?? 'General',
            'subject' => $template ? $this->letters->render($template->default_subject ?: $template->name, $context) : '',
            'content' => $template ? $this->letters->render($template->content, $context) : '',
            'content_type' => $template?->content_type ?? 'text',
            'status' => 'Draft',
            'letter_template_id' => $template?->id,
        ]);

        return view('letters.form', $this->formData($letter));
    }

    public function store(Request $request)
    {
        $this->requireModule();
        $data = $this->validated($request);

        $letter = DB::transaction(function () use ($data) {
            $context = $this->letters->sourceContext($data);
            if (! empty($data['letter_template_id']) && blank($data['content'])) {
                $template = LetterTemplate::find($data['letter_template_id']);
                $data['content'] = $this->letters->render($template?->content ?? '', $context);
                $data['subject'] = $data['subject'] ?: $this->letters->render($template?->default_subject ?? $template?->name ?? '', $context);
                $data['type'] = $data['type'] ?: ($template?->type ?? 'General');
                $data['content_type'] = $template?->content_type ?? 'text';
            }

            $letter = Letter::create($data + [
                'letter_number' => $this->letters->number($data['prefix'] ?? 'LTR'),
                'created_by' => auth()->id(),
            ]);
            $this->letters->recordVersion($letter);

            return $letter;
        });

        return redirect()->route('letters.show', $letter)->with('status', 'Letter created.');
    }

    public function show(Letter $letter)
    {
        $this->requireModule();

        $signatory = Schema::hasTable('signatories') ? Signatory::where('is_default', true)->where('is_active', true)->first() : null;

        return view('letters.show', [
            'letter' => $letter->load('client', 'site', 'project', 'invoice', 'receipt', 'payment', 'warranty', 'template', 'creator', 'approver', 'versions.creator', 'attachments.document'),
            'signatory' => $signatory,
        ]);
    }

    public function edit(Letter $letter)
    {
        $this->requireModule();

        return view('letters.form', $this->formData($letter));
    }

    public function update(Request $request, Letter $letter)
    {
        $this->requireModule();
        $data = $this->validated($request, $letter);
        unset($data['prefix']);

        $letter->update($data);
        $this->letters->recordVersion($letter);

        return redirect()->route('letters.show', $letter)->with('status', 'Letter updated.');
    }

    public function approve(Letter $letter)
    {
        $this->requireModule();
        $letter->update(['status' => 'Approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->letters->recordVersion($letter);

        return back()->with('status', 'Letter approved.');
    }

    public function submit(Letter $letter)
    {
        $this->requireModule();
        $letter->update(['status' => 'Pending']);
        $this->letters->recordVersion($letter);

        return back()->with('status', 'Letter submitted for approval.');
    }

    public function archive(Letter $letter)
    {
        $this->requireModule();
        $letter->update(['status' => 'Archived']);
        $this->letters->recordVersion($letter);

        return back()->with('status', 'Letter archived.');
    }

    public function deliveryForm(Letter $letter)
    {
        $this->requireModule();

        return view('letters.delivery', ['letter' => $letter->load('client')]);
    }

    public function deliver(Request $request, Letter $letter)
    {
        $this->requireModule();
        $data = $request->validate([
            'mode' => ['required', 'in:generate,email,portal'],
            'recipient' => ['nullable', 'email', 'max:255'],
            'cc' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string'],
        ]);

        if ($data['mode'] === 'email') {
            $recipient = $data['recipient'] ?: $letter->client?->email;
            if (! $recipient) {
                return back()->withErrors(['recipient' => 'A recipient email is required.']);
            }

            $cc = $this->validatedEmailList($data['cc'] ?? null);

            try {
                $this->outgoingMail->sendRaw(
                    $recipient,
                    $letter->subject,
                    $data['message'] ?: $letter->subject,
                    fn ($mail) => $mail->attachData($this->letters->pdf($letter)->output(), $letter->letter_number.'.pdf', ['mime' => 'application/pdf']),
                    $letter->business_id,
                    requireProfileSender: true,
                    cc: $cc,
                );
                $letter->update(['recipient' => $recipient, 'sent_at' => now(), 'delivery_status' => 'sent', 'status' => 'Sent']);
            } catch (\Throwable $e) {
                $letter->update(['recipient' => $recipient, 'delivery_status' => 'failed']);

                return back()->withErrors(['email' => 'Email failed: '.$this->outgoingMail->userFacingError($e)]);
            }
        } elseif ($data['mode'] === 'portal') {
            $letter->update(['portal_published_at' => now(), 'delivery_status' => 'portal_published', 'status' => 'Sent']);
        } else {
            $letter->update(['delivery_status' => 'generated']);
        }

        return redirect()->route('letters.show', $letter)->with('status', 'Letter delivery updated.');
    }

    public function download(Letter $letter, string $format = 'pdf')
    {
        $this->requireModule();
        $letter->load('client', 'site', 'project', 'invoice', 'receipt');

        if (strtolower($format) === 'docx') {
            return response()->download($this->letters->writeDocx($letter), Str::slug($letter->letter_number.'-'.$letter->subject).'.docx')->deleteFileAfterSend(true);
        }

        return $this->letters->pdf($letter)->download($letter->letter_number.'.pdf');
    }

    public function storeTemplate(Request $request)
    {
        $this->requireModule();
        LetterTemplate::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(LetterTemplate::TYPES)],
            'default_subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'content_type' => ['nullable', 'in:text,html'],
            'output_format' => ['required', 'in:PDF,DOCX'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active'), 'content_type' => $request->input('content_type', 'text')]);

        return back()->with('status', 'Letter template saved.');
    }

    public function fromInvoice(Invoice $invoice)
    {
        return redirect()->route('letters.create', ['invoice_id' => $invoice->id, 'client_id' => $invoice->client_id, 'site_id' => $invoice->site_id, 'project_id' => $invoice->project_id, 'template' => 'Outstanding Balance']);
    }

    public function fromReceipt(Receipt $receipt)
    {
        $receipt->load('invoice');

        return redirect()->route('letters.create', ['receipt_id' => $receipt->id, 'invoice_id' => $receipt->invoice_id, 'client_id' => $receipt->invoice?->client_id, 'project_id' => $receipt->project_id, 'template' => 'Acknowledgement']);
    }

    public function fromProject(Project $project)
    {
        return redirect()->route('letters.create', ['project_id' => $project->id, 'client_id' => $project->client_id, 'site_id' => $project->site_id, 'template' => 'Handover']);
    }

    public function fromWarranty(Warranty $warranty)
    {
        return redirect()->route('letters.create', ['warranty_id' => $warranty->id, 'client_id' => $warranty->client_id, 'project_id' => $warranty->project_id, 'site_id' => $warranty->site_id, 'template' => 'Warranty']);
    }

    public function publicVerify(Letter $letter)
    {
        $letter->load('client', 'template', 'creator', 'approver');
        $company = CompanySetting::withoutGlobalScope('business')
            ->where('business_id', $letter->business_id)
            ->first()
            ?: CompanySetting::first();

        return view('letters.verify', compact('letter', 'company'));
    }

    public function preview(Letter $letter)
    {
        $this->requireModule();

        return $this->letters->pdf($letter)->stream($letter->letter_number.'.pdf');
    }

    public function uploadImage(Request $request)
    {
        $this->requireModule();

        $data = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $file = $data['file'];
        $path = $file->store('letters/images', 'public');

        if (Schema::hasTable('document_media')) {
            DocumentMedia::create([
                'business_id' => ActiveBusiness::id(),
                'model_type' => Letter::class,
                'model_id' => 0,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'disk' => 'public',
            ]);
        }

        return response()->json(['location' => PublicUpload::url($path)]);
    }

    private function validated(Request $request, ?Letter $letter = null): array
    {
        $rules = [
            'prefix' => [$letter ? 'nullable' : 'required', 'string', 'max:12'],
            'letter_template_id' => ['nullable', Rule::exists('letter_templates', 'id')->where('business_id', ActiveBusiness::id())],
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'site_id' => ['nullable', Rule::exists('sites', 'id')->where('business_id', ActiveBusiness::id())],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())],
            'invoice_id' => ['nullable', Rule::exists('invoices', 'id')->where('business_id', ActiveBusiness::id())],
            'receipt_id' => ['nullable', Rule::exists('receipts', 'id')->where('business_id', ActiveBusiness::id())],
            'payment_id' => ['nullable', Rule::exists('payments', 'id')->where('business_id', ActiveBusiness::id())],
            'warranty_id' => ['nullable', Rule::exists('warranties', 'id')->where('business_id', ActiveBusiness::id())],
            'type' => ['required', Rule::in(Letter::TYPES)],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'content_type' => ['nullable', 'in:text,html'],
            'status' => ['required', Rule::in($letter ? Letter::STATUSES : ['Draft', 'Pending'])],
        ];

        return $request->validate($rules);
    }

    private function formData(Letter $letter): array
    {
        $this->letters->ensureDefaultTemplates();

        return [
            'letter' => $letter,
            'templates' => LetterTemplate::where('is_active', true)->orderBy('name')->get(),
            'clients' => Client::with('sites', 'projects')->orderBy('name')->get(),
            'sites' => Schema::hasTable('sites') ? Site::orderBy('site_name')->get() : collect(),
            'projects' => Schema::hasTable('projects') ? Project::orderBy('project_name')->get() : collect(),
            'invoices' => Invoice::source()->orderByDesc('id')->limit(100)->get(),
            'receipts' => Receipt::orderByDesc('id')->limit(100)->get(),
            'types' => Letter::TYPES,
            'statuses' => array_values(array_unique(array_filter(['Draft', 'Pending', $letter->status]))),
            'signatories' => Schema::hasTable('signatories') ? Signatory::where('is_active', true)->orderBy('name')->get() : collect(),
        ];
    }

    private function sourceData(Request $request): array
    {
        return array_filter($request->only(['client_id', 'site_id', 'project_id', 'invoice_id', 'receipt_id', 'payment_id', 'warranty_id']), fn ($value) => filled($value));
    }

    private function templateFor(?string $name, ?string $id): ?LetterTemplate
    {
        $this->letters->ensureDefaultTemplates();
        if ($id) {
            return LetterTemplate::find($id);
        }

        return $name ? LetterTemplate::where('name', $name)->first() : null;
    }

    private function requireModule(): void
    {
        abort_unless(Schema::hasTable('letters') && Schema::hasTable('letter_templates'), 404);
    }
}
