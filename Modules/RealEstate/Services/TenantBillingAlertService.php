<?php

namespace Modules\RealEstate\Services;

use App\Models\Business;
use App\Models\Tenant as PlatformTenant;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\RealEstate\Models\Tenant;

class TenantBillingAlertService
{
    public function __construct(private TenantLedgerService $ledger) {}

    public function updateSettings(Tenant $tenant, array $data): Tenant
    {
        if (array_key_exists('email', $data)) {
            $tenant->client?->update(['email' => $data['email'] ?: null]);
        }

        $tenant->update([
            'billing_alert_enabled' => (bool) ($data['billing_alert_enabled'] ?? false),
            'billing_alert_frequency' => $data['billing_alert_frequency'] ?? 'Monthly',
            'billing_alert_day' => max(1, min((int) ($data['billing_alert_day'] ?? 1), 28)),
            'billing_alert_subject' => $data['billing_alert_subject'] ?? null,
        ]);

        return $tenant->refresh()->load('client');
    }

    public function sendDueAlerts(?Carbon $date = null): int
    {
        $date ??= now();
        $sent = 0;

        Business::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('industry', 'RealEstate')
            ->orderBy('id')
            ->chunkById(50, function ($businesses) use ($date, &$sent) {
                foreach ($businesses as $business) {
                    $platformTenant = $business->tenant_id ? PlatformTenant::find($business->tenant_id) : null;
                    if ($platformTenant) {
                        ActiveTenant::switchTo($platformTenant);
                    }
                    ActiveBusiness::switchTo($business);

                    Tenant::with('client')
                        ->where('billing_alert_enabled', true)
                        ->whereIn('status', ['Prospect', 'Active', 'Notice Given', 'Moving Out'])
                        ->chunkById(100, function ($tenants) use ($date, &$sent) {
                            foreach ($tenants as $tenant) {
                                if ($this->isDue($tenant, $date) && $this->sendAlert($tenant, $date)) {
                                    $sent++;
                                }
                            }
                        });
                }
            });

        return $sent;
    }

    public function sendAlert(Tenant $tenant, ?Carbon $date = null): bool
    {
        $date ??= now();
        $tenant->load('client', 'leases.property', 'leases.unit');
        $email = $tenant->client?->email;

        if (! $email) {
            $tenant->emailLogs()->create([
                'recipient_email' => '',
                'subject' => $this->subject($tenant, $date),
                'message' => null,
                'status' => 'failed',
                'error' => 'Client does not have an email address.',
            ]);

            return false;
        }

        $summary = $this->ledger->financialSummary($tenant);
        $lease = $tenant->leases->where('status', 'Active')->sortByDesc('id')->first();
        $subject = $this->subject($tenant, $date);
        $message = $this->message($tenant, $summary, $lease, $date);

        try {
            Mail::raw($message, fn ($mail) => $mail->to($email)->subject($subject));

            $tenant->emailLogs()->create([
                'recipient_email' => $email,
                'subject' => $subject,
                'message' => $message,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $tenant->update(['last_billing_alert_sent_at' => now()]);
            $this->recordNotification($tenant, $subject, $message);

            return true;
        } catch (\Throwable $e) {
            report($e);

            $tenant->emailLogs()->create([
                'recipient_email' => $email,
                'subject' => $subject,
                'message' => $message,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function isDue(Tenant $tenant, Carbon $date): bool
    {
        $day = min((int) ($tenant->billing_alert_day ?: 1), $date->daysInMonth);
        if ((int) $date->day !== $day) {
            return false;
        }

        $lastSent = $tenant->last_billing_alert_sent_at;
        if (($tenant->billing_alert_frequency ?? 'Monthly') === 'Quarterly') {
            if (! in_array((int) $date->month, [1, 4, 7, 10], true)) {
                return false;
            }

            return ! $lastSent || ! ($lastSent->year === $date->year && $lastSent->quarter === $date->quarter);
        }

        return ! $lastSent || ! ($lastSent->year === $date->year && $lastSent->month === $date->month);
    }

    private function subject(Tenant $tenant, Carbon $date): string
    {
        return $tenant->billing_alert_subject ?: 'Tenant billing statement - '.$date->format('F Y');
    }

    private function message(Tenant $tenant, array $summary, $lease, Carbon $date): string
    {
        $lines = [
            'Dear '.$tenant->client?->name.',',
            '',
            'Your '.$tenant->billing_alert_frequency.' real estate billing alert for '.$date->format('d M Y').' is ready.',
            '',
            'Tenant Number: '.$tenant->tenant_number,
            'Property: '.($lease?->property?->property_name ?? 'N/A'),
            'Unit: '.($lease?->unit?->unit_number ?? 'N/A'),
            'Outstanding Balance: KES '.number_format((float) $summary['outstanding_balance'], 2),
            'Total Charges: KES '.number_format((float) $summary['total_charges'], 2),
            'Total Paid: KES '.number_format((float) $summary['total_paid'], 2),
            'Utility Charges: KES '.number_format((float) $summary['utility_charges'], 2),
            'Service Charges: KES '.number_format((float) $summary['service_charges'], 2),
            '',
            'Please contact the property office if any item needs review.',
        ];

        return implode("\n", $lines);
    }

    private function recordNotification(Tenant $tenant, string $subject, string $message): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')->insert([
            'tenant_id' => ActiveTenant::id(),
            'business_id' => ActiveBusiness::id(),
            'notifiable_type' => $tenant->getMorphClass(),
            'notifiable_id' => $tenant->id,
            'notification_type' => 'Real Estate Billing Alert',
            'delivery_channel' => 'Email',
            'title' => $subject,
            'body' => str($message)->limit(500)->toString(),
            'status' => 'Delivered',
            'payload' => json_encode(['tenant_id' => $tenant->id, 'client_id' => $tenant->client_id]),
            'delivered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
