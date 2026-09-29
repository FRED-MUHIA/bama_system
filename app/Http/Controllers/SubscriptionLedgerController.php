<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionLedgerController extends Controller
{
    public function index(Request $request)
    {
        $statuses = array_merge(['paid', 'initiated'], array_column(PaymentStatus::cases(), 'value'));
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::in($statuses)],
            'provider' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
        ]);
        $query = SubscriptionPayment::query()
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['provider'] ?? null, fn ($q, $value) => $q->where('provider', $value))
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereRaw('COALESCE(paid_at, created_at) >= ?', [$value.' 00:00:00']))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereRaw('COALESCE(paid_at, created_at) < ?', [\Illuminate\Support\Carbon::parse($value)->addDay()->toDateString()]))
            ->when($filters['q'] ?? null, function ($q, $value) {
                $q->where(function ($search) use ($value) {
                    $like = '%'.$value.'%';
                    $search->where('merchant_reference', 'like', $like)
                        ->orWhere('provider_payment_id', 'like', $like)
                        ->orWhere('provider_receipt', 'like', $like)
                        ->orWhereHas('invoice', fn ($invoice) => $invoice->where('invoice_number', 'like', $like)->orWhere('customer_name', 'like', $like)->orWhere('billing_email', 'like', $like));
                });
            });

        return view('platform.subscription-ledger', [
            'payments' => (clone $query)->with(['invoice.plan', 'tenant' => fn ($q) => $q->withTrashed()])
                ->orderByRaw('COALESCE(paid_at, created_at) DESC')->orderByDesc('id')->paginate(25)->withQueryString(),
            'totals' => (clone $query)->whereIn('status', ['paid', 'successful'])
                ->selectRaw('currency, SUM(amount) as total, COUNT(*) as payment_count')->groupBy('currency')->get(),
            'statusCounts' => (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'providers' => SubscriptionPayment::query()->distinct()->orderBy('provider')->pluck('provider'),
            'statuses' => $statuses,
            'filters' => $filters,
        ]);
    }
}
