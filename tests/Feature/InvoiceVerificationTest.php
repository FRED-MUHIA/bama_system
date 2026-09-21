<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createInvoice(array $attributes = []): Invoice
    {
        $business = Business::where('slug', 'bama')->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))
            ->withSession(['active_business_id' => $business->id]);
        $clientId = DB::table('clients')->insertGetId([
            'business_id' => $business->id, 'name' => 'Verification Client',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Invoice::create(array_replace([
            'client_id' => $clientId, 'invoice_number' => 'INV-VERIFY-1',
            'invoice_date' => now(), 'subtotal' => 900, 'total' => 900, 'balance' => 900,
        ], $attributes));
    }

    public function test_new_invoices_receive_a_persisted_public_token(): void
    {
        $invoice = $this->createInvoice();

        $this->assertSame(48, strlen($invoice->public_token));
        $this->assertSame($invoice->public_token, $invoice->fresh()->public_token);
        $this->get(route('invoices.show', $invoice))->assertOk();
    }

    public function test_opening_a_legacy_invoice_repairs_its_link_and_preserves_accounting_data(): void
    {
        $invoice = $this->createInvoice();
        DB::table('invoices')->where('id', $invoice->id)->update(['public_token' => null]);
        $before = (array) DB::table('invoices')->find($invoice->id);
        $journalCount = DB::table('journal_entries')->count();

        $this->get(route('invoices.show', $invoice))->assertOk();
        $invoice->refresh();
        $this->assertSame(48, strlen($invoice->public_token));
        $after = (array) DB::table('invoices')->find($invoice->id);
        unset($before['public_token'], $after['public_token']);
        $this->assertSame($before, $after);
        $this->assertSame($journalCount, DB::table('journal_entries')->count());

        $service = app(InvoiceVerificationService::class);
        $url = $service->url($invoice);
        $this->assertStringStartsWith('data:image/svg+xml', $service->qrCodeDataUri($invoice));
        auth()->logout();
        $this->get($url)->assertOk()->assertSee($invoice->invoice_number);
    }

    public function test_stale_invoice_instances_reuse_the_same_repaired_token(): void
    {
        $invoice = $this->createInvoice();
        DB::table('invoices')->where('id', $invoice->id)->update(['public_token' => '']);
        $first = $invoice->fresh();
        $second = $invoice->fresh();
        $service = app(InvoiceVerificationService::class);

        $this->assertSame($service->url($first), $service->url($second));
        $this->assertSame($first->public_token, $invoice->fresh()->public_token);
    }

    public function test_existing_public_tokens_are_not_replaced(): void
    {
        $invoice = $this->createInvoice(['public_token' => 'existing-verification-token']);
        $service = app(InvoiceVerificationService::class);

        $this->assertSame(route('public.invoices.show', 'existing-verification-token'), $service->url($invoice));
        $this->assertSame('existing-verification-token', $invoice->fresh()->public_token);
    }
}
