@php
    $isPartPaymentInvoice = $type === 'Invoice' && $document->isAllocationInvoice();
    $industryContext = $document->industry_context ?? [];
    $recipientProfile = $document->recipient_profile ?? [];
@endphp
<div class="card"><div class="card-body">
    <div class="row g-3 mb-4">
        <div class="col-md-6"><h2 class="h5">{{ $isPartPaymentInvoice ? 'Allocation Invoice' : $type }} Details</h2><p class="mb-1"><strong>{{ $number }}</strong></p>@if($document->industry_reference)<p class="mb-1">Reference: <strong>{{ $document->industry_reference }}</strong></p>@endif<p class="mb-1">{{ $date?->format('d M Y') }}</p>@if($isPartPaymentInvoice && $document->parentInvoice)<p class="mb-1">Parent: <a href="{{ route('invoices.show',$document->parentInvoice) }}">{{ $document->parentInvoice->invoice_number }}</a></p>@endif<span class="status-pill">{{ $status }}</span></div>
        <div class="col-md-6"><h2 class="h5">Client</h2><p class="mb-1">{{ $recipientProfile['name'] ?? $document->client->name }}</p>@if(!empty($recipientProfile['tenant_number']))<p class="mb-1">Tenant: {{ $recipientProfile['tenant_number'] }}</p>@endif<p class="mb-1">{{ $document->client->company_name }}</p><p class="mb-1">{{ $recipientProfile['email'] ?? $document->client->email }}</p><p class="mb-0 text-muted">{{ $recipientProfile['address'] ?? $document->client->address }}</p>@if($document->relationLoaded('project') && $document->project)<p class="mb-1 mt-2">Project: <a href="{{ route('projects.show',$document->project) }}">{{ $document->project->project_name }}</a></p>@endif @if($document->relationLoaded('site') && $document->site)<p class="mb-0 text-muted">Site: {{ $document->site->site_name }}</p>@endif</div>
    </div>
    @if($document->industry_module === 'real_estate')
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <h2 class="h6">Real Estate Profile</h2>
                @if(!empty($recipientProfile['phone']))<p class="mb-1">Phone: {{ $recipientProfile['phone'] }}</p>@endif
                @if(!empty($recipientProfile['id_number']))<p class="mb-1">ID Number: {{ $recipientProfile['id_number'] }}</p>@endif
                @if(!empty($recipientProfile['passport_number']))<p class="mb-0">Passport: {{ $recipientProfile['passport_number'] }}</p>@endif
            </div>
            <div class="col-md-6">
                <h2 class="h6">Property Context</h2>
                @if(!empty($industryContext['property_name']))<p class="mb-1">{{ $industryContext['property_name'] }} @if(!empty($industryContext['property_code']))({{ $industryContext['property_code'] }})@endif</p>@endif
                @if(!empty($industryContext['unit_number']))<p class="mb-1">Unit: {{ $industryContext['unit_number'] }} @if(!empty($industryContext['unit_type']))- {{ $industryContext['unit_type'] }}@endif</p>@endif
                @if(!empty($industryContext['lease_number']))<p class="mb-1">Lease: {{ $industryContext['lease_number'] }}</p>@endif
                @if(!empty($industryContext['source_reference']))<p class="mb-0">Source: {{ $industryContext['source_reference'] }}</p>@endif
            </div>
        </div>
    @endif
    @if($document->industry_module === 'printing_branding')
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <h2 class="h6">Printing Job</h2>
                @if(!empty($industryContext['job_number']))<p class="mb-1">Job: <strong>{{ $industryContext['job_number'] }}</strong></p>@endif
                @if(!empty($industryContext['ticket_number']))<p class="mb-1">Ticket: {{ $industryContext['ticket_number'] }}</p>@endif
                @if(!empty($industryContext['invoice_type']))<p class="mb-1">Invoice Type: {{ $industryContext['invoice_type'] }}</p>@endif
                @if(!empty($industryContext['job_status']))<p class="mb-0">Production Status: {{ $industryContext['job_status'] }}</p>@endif
            </div>
            <div class="col-md-6">
                <h2 class="h6">Production Context</h2>
                @if(!empty($industryContext['product_name']))<p class="mb-1">Product: {{ $industryContext['product_name'] }}</p>@endif
                @if(isset($industryContext['quantity']))<p class="mb-1">Quantity: {{ number_format((float) $industryContext['quantity'], 3) }}</p>@endif
                @if(!empty($industryContext['delivery_date']))<p class="mb-1">Delivery Date: {{ $industryContext['delivery_date'] }}</p>@endif
                @if(!empty($industryContext['machine']))<p class="mb-0">Machine: {{ $industryContext['machine'] }}</p>@endif
            </div>
            @if(!empty($industryContext['specifications']))
                <div class="col-12">
                    <h2 class="h6">Specifications</h2>
                    <p class="text-muted mb-0">{{ collect($industryContext['specifications'])->map(fn($value, $key) => $key.': '.$value)->implode(', ') }}</p>
                </div>
            @endif
        </div>
    @endif
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Title</th><th>Description</th><th>Qty</th><th>Unit</th><th>Discount</th><th>Tax</th><th>Total</th></tr></thead><tbody>
        @foreach($document->items as $item)<tr><td>{{ $item->title }}</td><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price,2) }}</td><td>{{ number_format($item->discount,2) }}</td><td>{{ $item->tax_rate }}%</td><td>{{ number_format($item->line_total,2) }}</td></tr>@endforeach
    </tbody></table></div>
    <div class="row justify-content-end"><div class="col-md-4"><table class="table">@if($isPartPaymentInvoice)<tr><th>Allocated Amount</th><td class="text-end fw-bold">{{ number_format($document->part_payment_amount,2) }}</td></tr>@if($document->parentInvoice)<tr><th>Source Total</th><td class="text-end">{{ number_format($document->parentInvoice->total,2) }}</td></tr>@endif @else<tr><th>Subtotal</th><td class="text-end">{{ number_format($document->subtotal,2) }}</td></tr><tr><th>Discount</th><td class="text-end">{{ number_format($document->discount_total,2) }}</td></tr><tr><th>Tax</th><td class="text-end">{{ number_format($document->tax_total,2) }}</td></tr><tr><th>Total</th><td class="text-end fw-bold">{{ number_format($document->total,2) }}</td></tr>@isset($document->balance)<tr><th>Balance</th><td class="text-end">{{ number_format($document->balance,2) }}</td></tr>@endisset @endif</table></div></div>
    @if($document->terms)<h3 class="h6">Terms</h3><p class="text-muted">{{ $document->terms }}</p>@endif
    @if($document->emailLogs->count())<h3 class="h6 mt-4">Email History</h3>@foreach($document->emailLogs as $log)<div class="border-top py-2">{{ $log->recipient_email }} · {{ $log->subject }} <span class="float-end">{{ $log->status }}</span></div>@endforeach @endif
</div></div>
