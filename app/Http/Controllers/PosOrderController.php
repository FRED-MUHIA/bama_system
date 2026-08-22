<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\PosOrder;
use App\Models\Product;
use App\Services\DocumentService;
use App\Services\StockService;
use App\Support\ActiveBusiness;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PosOrderController extends Controller
{
    public function __construct(private DocumentService $documents) {}

    public function index()
    {
        return view('pos-orders.index', [
            'orders' => PosOrder::with('client', 'paymentMethod', 'invoice', 'items')->latest()->paginate(12),
        ]);
    }

    public function create()
    {
        return view('pos-orders.form', [
            'order' => new PosOrder(['order_date' => now(), 'status' => 'pending']),
            'clients' => Client::orderBy('name')->get(),
            'methods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('category')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $order = DB::transaction(fn () => $this->saveOrder(new PosOrder, $request));

        return redirect()->route('pos-orders.show', $order)->with('status', 'POS order saved.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'orders_csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('orders_csv')->getRealPath(), 'r');
        if (! $handle) {
            return back()->withErrors(['orders_csv' => 'The CSV file could not be opened.']);
        }

        $headers = $this->normalizeCsvHeaders(fgetcsv($handle) ?: []);
        $required = ['date', 'order', 'status', 'customer', 'customer_type', 'items_sold', 'net_sales'];
        $missing = array_diff($required, $headers);

        if ($missing) {
            fclose($handle);

            return back()->withErrors([
                'orders_csv' => 'Missing CSV columns: '.implode(', ', array_map(fn ($header) => str_replace('_', ' ', $header), $missing)).'.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $skipped = [];
        $line = 1;
        $hasCustomerType = Schema::hasColumn('pos_orders', 'customer_type');

        DB::transaction(function () use ($handle, $headers, $hasCustomerType, &$created, &$updated, &$skipped, &$line) {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if ($this->isBlankCsvRow($row)) {
                    continue;
                }

                $data = array_combine($headers, array_slice(array_pad($row, count($headers), ''), 0, count($headers)));
                $orderNumber = trim((string) ($data['order'] ?? ''));

                if ($orderNumber === '') {
                    $skipped[] = "Line {$line}: missing order number.";

                    continue;
                }

                try {
                    $orderDate = $this->parseCsvDate((string) $data['date']);
                } catch (\Throwable) {
                    $skipped[] = "Line {$line}: invalid date.";

                    continue;
                }

                $quantity = max($this->csvNumber($data['items_sold'] ?? 0), 0.01);
                $netSales = max($this->csvNumber($data['net_sales'] ?? 0), 0);
                $products = trim((string) ($data['products'] ?? ''));
                $status = $this->normalizeCsvStatus((string) ($data['status'] ?? ''));
                $customerName = trim((string) ($data['customer'] ?? ''));

                $order = PosOrder::where('order_number', $orderNumber)->first();
                $wasRecentlyCreated = false;

                if (! $order) {
                    $order = new PosOrder([
                        'order_number' => $orderNumber,
                        'tracking_key' => Str::upper(Str::random(12)),
                    ]);
                    $wasRecentlyCreated = true;
                }

                $orderData = [
                    'order_date' => $orderDate,
                    'status' => $status,
                    'customer_name' => $customerName,
                    'subtotal' => $netSales,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'custom_amount' => 0,
                    'total' => $netSales,
                    'amount_paid' => in_array($status, ['paid', 'approved'], true) ? $netSales : 0,
                    'approved_at' => $status === 'approved' ? now() : null,
                ];

                if ($hasCustomerType) {
                    $orderData['customer_type'] = trim((string) ($data['customer_type'] ?? '')) ?: null;
                }

                $order->fill($orderData)->save();

                $order->items()->delete();
                foreach ($this->csvItems($products, $quantity, $netSales) as $item) {
                    $order->items()->create($item);
                }
                $this->syncOrderPayments($order, (float) $order->amount_paid);

                $wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        fclose($handle);

        $message = "CSV import finished. Created {$created} orders and updated {$updated} orders.";
        if ($skipped) {
            $message .= ' Skipped '.count($skipped).' rows: '.implode(' ', array_slice($skipped, 0, 5));
        }

        return redirect()->route('pos-orders.index')->with('status', $message);
    }

    public function show(PosOrder $posOrder)
    {
        $relationships = ['client', 'paymentMethod', 'invoice', 'items.product'];
        $paymentsReady = Schema::hasTable('pos_order_payments');

        if ($paymentsReady) {
            $relationships[] = 'payments.paymentMethod';
        }

        return view('pos-orders.show', [
            'order' => $posOrder->load($relationships),
            'methods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'paymentsReady' => $paymentsReady,
        ]);
    }

    public function edit(PosOrder $posOrder)
    {
        return view('pos-orders.form', [
            'order' => $posOrder->load('items'),
            'clients' => Client::orderBy('name')->get(),
            'methods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('category')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PosOrder $posOrder)
    {
        $order = DB::transaction(fn () => $this->saveOrder($posOrder, $request));

        return redirect()->route('pos-orders.show', $order)->with('status', 'POS order updated.');
    }

    public function track(string $key)
    {
        $order = PosOrder::withoutGlobalScope('business')->with('items')->where('tracking_key', $key)->firstOrFail();
        $order->setRelation('client', $order->client_id ? Client::withoutGlobalScope('business')->find($order->client_id) : null);
        $order->setRelation('paymentMethod', $order->payment_method_id ? PaymentMethod::withoutGlobalScope('business')->find($order->payment_method_id) : null);

        $products = Product::withoutGlobalScope('business')
            ->whereIn('id', $order->items->pluck('product_id')->filter()->all())
            ->get()
            ->keyBy('id');
        $order->items->each(fn ($item) => $item->setRelation('product', $products->get($item->product_id)));

        return view('pos-orders.track', [
            'order' => $order,
        ]);
    }

    public function report(Request $request)
    {
        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now();
        $orders = PosOrder::with('client', 'items.product.category')
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->latest()
            ->get();

        $activeOrders = $orders->where('status', '!=', 'cancelled');
        $items = $activeOrders->flatMap->items;

        return view('pos-orders.report', [
            'from' => $from,
            'to' => $to,
            'orders' => $orders,
            'revenue' => $activeOrders->sum('amount_paid'),
            'pending' => $orders->where('status', 'pending')->sum('total'),
            'unitsSold' => $items->sum('quantity'),
            'topProducts' => $items->groupBy(fn ($item) => $item->product?->name ?: $item->title ?: $item->description)
                ->map(fn ($group, $name) => ['name' => $name, 'qty' => $group->sum('quantity'), 'total' => $group->sum('line_total')])
                ->sortByDesc('total')
                ->take(8),
        ]);
    }

    public function destroy(PosOrder $posOrder)
    {
        if ($posOrder->status !== 'cancelled') {
            app(StockService::class)->syncSaleItems(
                $posOrder->items()->get(['product_id', 'quantity']),
                collect(),
                $posOrder,
                'POS order deleted '.$posOrder->order_number
            );
        }

        $posOrder->delete();

        return redirect()->route('pos-orders.index')->with('status', 'POS order deleted.');
    }

    public function recordPayment(Request $request, PosOrder $posOrder)
    {
        if (! Schema::hasTable('pos_order_payments')) {
            return back()->withErrors(['amount' => 'POS payment records are not ready yet. Please run the latest database migrations.']);
        }

        $remaining = max((float) $posOrder->total - (float) $posOrder->amount_paid, 0);

        if ($remaining <= 0) {
            return back()->withErrors(['amount' => 'This POS order is already fully paid.']);
        }

        $data = $request->validate([
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('business_id', ActiveBusiness::id())],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$remaining],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($posOrder, $data) {
            $posOrder->payments()->create($data);
            $paid = min((float) $posOrder->total, (float) $posOrder->amount_paid + (float) $data['amount']);
            $status = $paid >= (float) $posOrder->total && ! in_array($posOrder->status, ['approved', 'cancelled'], true)
                ? 'paid'
                : $posOrder->status;

            $posOrder->update([
                'payment_method_id' => $data['payment_method_id'] ?: $posOrder->payment_method_id,
                'amount_paid' => $paid,
                'status' => $status,
            ]);
        });

        return back()->with('status', 'POS payment recorded.');
    }

    private function saveOrder(PosOrder $order, Request $request): PosOrder
    {
        $data = $request->validate([
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('business_id', ActiveBusiness::id())],
            'order_date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:100'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_address' => ['nullable', 'string'],
            'customer_type' => ['nullable', 'string', 'max:255'],
            'custom_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,paid,approved,cancelled'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = $this->documents->normalizeItems($data['items']);
        unset($data['items']);
        if (! Schema::hasColumn('pos_orders', 'customer_type')) {
            unset($data['customer_type']);
        }
        $totals = $this->documents->totals($items);
        $total = $totals['total'] + (float) ($data['custom_amount'] ?? 0);
        $amountPaid = min((float) ($data['amount_paid'] ?? 0), $total);
        $oldItems = $order->exists && $order->status !== 'cancelled'
            ? $order->items()->get(['product_id', 'quantity'])
            : collect();

        $order->fill($data + [
            'order_number' => $order->order_number ?: $this->documents->number('pos_order'),
            'tracking_key' => $order->tracking_key ?: Str::upper(Str::random(12)),
            'approved_at' => $data['status'] === 'approved' ? ($order->approved_at ?: now()) : null,
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discountTotal'],
            'tax_total' => $totals['taxTotal'],
            'custom_amount' => $data['custom_amount'] ?? 0,
            'total' => $total,
            'amount_paid' => $amountPaid,
        ])->save();

        $order->items()->delete();
        foreach ($items as $item) {
            $order->items()->create($item + ['line_total' => $this->documents->lineTotal($item)]);
        }

        app(StockService::class)->syncSaleItems(
            $oldItems,
            $data['status'] === 'cancelled' ? collect() : collect($items),
            $order,
            'POS order '.$order->order_number
        );

        $this->syncOrderPayments($order, $amountPaid);

        return $order;
    }

    private function syncOrderPayments(PosOrder $order, float $targetPaid): void
    {
        if (! Schema::hasTable('pos_order_payments')) {
            return;
        }

        $targetPaid = round(max($targetPaid, 0), 2);
        $currentPaid = round((float) $order->payments()->sum('amount'), 2);

        if ($targetPaid === $currentPaid) {
            return;
        }

        if ($targetPaid <= 0) {
            $order->payments()->delete();

            return;
        }

        if ($targetPaid > $currentPaid) {
            $order->payments()->create([
                'payment_method_id' => $order->payment_method_id,
                'amount' => $targetPaid - $currentPaid,
                'payment_date' => $order->order_date ?: now(),
                'reference' => 'POS order update',
                'notes' => 'Auto-adjusted from POS order edit.',
            ]);

            return;
        }

        $remainingReduction = $currentPaid - $targetPaid;
        $payments = $order->payments()->latest('id')->get();

        foreach ($payments as $payment) {
            if ($remainingReduction <= 0) {
                break;
            }

            if ((float) $payment->amount <= $remainingReduction + 0.001) {
                $remainingReduction = round($remainingReduction - (float) $payment->amount, 2);
                $payment->delete();

                continue;
            }

            $payment->update(['amount' => round((float) $payment->amount - $remainingReduction, 2)]);
            $remainingReduction = 0;
        }
    }

    private function normalizeCsvHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $normalized = Str::of((string) $header)
                ->lower()
                ->replace(['#', '/', '-'], ' ')
                ->squish()
                ->replace(' ', '_')
                ->toString();

            return match ($normalized) {
                'created_at', 'order_date', 'sale_date' => 'date',
                'order_id', 'order_no', 'order_number', 'order_name', 'name' => 'order',
                'financial_status', 'fulfillment_status', 'payment_status' => 'status',
                'customer_name', 'client', 'client_name', 'billing_name' => 'customer',
                'type', 'client_type', 'customer_category' => 'customer_type',
                'product', 'product_name', 'item', 'item_name', 'lineitem_name', 'line_item_name', 'variant', 'sku' => 'products',
                'quantity', 'qty', 'items', 'item_count', 'lineitem_quantity', 'line_item_quantity' => 'items_sold',
                'sales', 'net_sale', 'total', 'amount', 'order_total', 'subtotal', 'net_amount' => 'net_sales',
                default => $normalized,
            };
        }, $headers);
    }

    private function isBlankCsvRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function parseCsvDate(string $date): string
    {
        $date = trim($date);

        foreach (['Y-m-d', 'm/d/Y', 'd/m/Y', 'd-m-Y', 'M d Y', 'd M Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse($date)->toDateString();
    }

    private function normalizeCsvStatus(string $status): string
    {
        $status = Str::of($status)->lower()->trim()->toString();

        return match ($status) {
            'paid' => 'paid',
            'complete', 'completed', 'settled' => 'paid',
            'approve', 'approved' => 'approved',
            'cancel', 'canceled', 'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    private function csvNumber(mixed $value): float
    {
        return (float) preg_replace('/[^\d.\-]/', '', (string) $value);
    }

    private function csvItems(string $products, float $quantity, float $netSales): array
    {
        $names = collect(preg_split('/\s*[;|]\s*/', $products) ?: [])
            ->map(fn ($name) => trim($name))
            ->filter()
            ->values();

        if ($names->count() <= 1) {
            $title = $products !== '' ? $products : 'Imported sale';

            return [[
                'title' => $title,
                'description' => $title,
                'quantity' => $quantity,
                'unit_price' => $quantity > 0 ? $netSales / $quantity : $netSales,
                'discount' => 0,
                'tax_rate' => 0,
                'line_total' => $netSales,
            ]];
        }

        $qtyPerItem = $quantity / $names->count();
        $totalPerItem = $netSales / $names->count();

        return $names->map(fn ($name) => [
            'title' => $name,
            'description' => $name,
            'quantity' => $qtyPerItem,
            'unit_price' => $qtyPerItem > 0 ? $totalPerItem / $qtyPerItem : $totalPerItem,
            'discount' => 0,
            'tax_rate' => 0,
            'line_total' => $totalPerItem,
        ])->all();
    }

    public function approve(PosOrder $posOrder)
    {
        $posOrder->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->syncOrderPayments($posOrder, (float) $posOrder->amount_paid);

        return back()->with('status', 'POS order approved.');
    }
}
