<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantTheme;
use App\Models\User;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    public function provision(array $data, User $owner): Tenant
    {
        return DB::transaction(function () use ($data, $owner) {
            $tenant = Tenant::create($this->tenantColumns([
                'name' => $data['tenant_name'],
                'slug' => ActiveTenant::slug($data['tenant_name']),
                'industry' => $data['industry'] ?? 'ProfessionalServices',
                'sub_industry' => $data['sub_industry'] ?? null,
                'settings' => $this->initialSettings($data),
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
            ]));

            Business::create([
                'tenant_id' => $tenant->id,
                'name' => $data['business_name'] ?? $data['tenant_name'],
                'slug' => ActiveBusiness::slug($data['business_name'] ?? $data['tenant_name']),
                'industry' => $tenant->industry,
            ]);

            $tenant->users()->syncWithoutDetaching([
                $owner->id => ['role' => 'owner', 'status' => 'active', 'joined_at' => now()],
            ]);

            $plan = Plan::where('slug', $data['plan'] ?? 'starter')->first() ?: Plan::where('slug', 'starter')->first();
            if ($plan) {
                $tenant->subscription()->create([
                    'plan_id' => $plan->id,
                    'status' => 'trialing',
                    'starts_at' => now(),
                    'trial_ends_at' => now()->addDays(14),
                    'renews_at' => now()->addMonth(),
                ]);
            }

            TenantTheme::create([
                'tenant_id' => $tenant->id,
                'primary_color' => '#00A651',
                'secondary_color' => '#000000',
                'accent_color' => '#00A651',
            ]);

            app(ModuleRegistry::class)->enableDefaultsFor($tenant);
            ActiveTenant::switchTo($tenant);
            if ($business = $tenant->businesses()->first()) {
                ActiveBusiness::switchTo($business);
            }

            return $tenant;
        });
    }

    public function provisionRegistration(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $account = $payload['account'];
            $company = $payload['company'];
            $plan = $payload['plan'] ?? 'starter';

            $userAttributes = [
                'name' => $account['name'],
                'email' => strtolower($account['email']),
                'username' => $this->uniqueUsername($account['name']),
                'password' => $account['password'],
                'role' => 'admin',
                'is_active' => true,
                'enable_password_login' => true,
                'enable_otp_login' => true,
                'enable_magic_link_login' => true,
                'status' => 'Active',
                'phone' => $account['phone'] ?? null,
                'timezone' => $company['timezone'] ?? config('app.timezone'),
                'date_joined' => now()->toDateString(),
                'password_changed_at' => now(),
            ];

            $user = User::create(collect($userAttributes)
                ->filter(fn ($value, $column) => Schema::hasColumn('users', $column))
                ->all());

            $tenant = $this->provision([
                'tenant_name' => $company['company_name'],
                'business_name' => $company['company_name'],
                'industry' => $company['industry'],
                'sub_industry' => $company['sub_industry'],
                'plan' => $plan,
            ], $user);

            $userUpdates = collect([
                'current_tenant_id' => $tenant->id,
                'timezone' => $company['timezone'] ?? config('app.timezone'),
            ])->filter(fn ($value, $column) => Schema::hasColumn('users', $column))->all();

            if ($userUpdates) {
                $user->update($userUpdates);
            }

            $tenant->update($this->tenantColumns([
                'sub_industry' => $company['sub_industry'],
                'settings' => array_merge($tenant->settings ?? [], [
                    'country' => $company['country'],
                    'currency' => $company['currency'],
                    'timezone' => $company['timezone'],
                    'sub_industry' => $company['sub_industry'],
                    'agriculture_configuration' => $company['industry'] === 'agriculture'
                        ? $this->agricultureConfiguration($company['sub_industry'])
                        : ($tenant->settings['agriculture_configuration'] ?? null),
                    'printing_branding_configuration' => $company['industry'] === 'printing_branding'
                        ? $this->printingBrandingConfiguration($company['sub_industry'])
                        : ($tenant->settings['printing_branding_configuration'] ?? null),
                    'industry_dashboard' => app(IndustrySetupService::class)->dashboardFeatures($company['industry'], $company['sub_industry']),
                    'registration_source' => 'public_signup',
                ]),
            ]));

            app(IndustrySetupService::class)->provision($tenant);
            app(IamService::class)->bootstrapBusinessDefaults($user);

            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }

            return ['tenant' => $tenant->refresh(), 'user' => $user->refresh()];
        });
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->limit(24, '')->value() ?: 'user';
        $username = $base;
        $counter = 1;

        while (Schema::hasColumn('users', 'username') && User::where('username', $username)->exists()) {
            $counter++;
            $username = $base.'.'.$counter;
        }

        return $username;
    }

    private function initialSettings(array $data): array
    {
        if (($data['industry'] ?? null) === 'printing_branding') {
            return [
                'sub_industry' => $data['sub_industry'] ?? null,
                'printing_branding_configuration' => $this->printingBrandingConfiguration($data['sub_industry'] ?? null),
            ];
        }

        if (($data['industry'] ?? null) !== 'agriculture') {
            return [];
        }

        return [
            'sub_industry' => $data['sub_industry'] ?? null,
            'agriculture_configuration' => $this->agricultureConfiguration($data['sub_industry'] ?? null),
        ];
    }

    private function agricultureConfiguration(?string $subIndustry): array
    {
        $package = file_exists(base_path('Modules/Agriculture/module.php')) ? require base_path('Modules/Agriculture/module.php') : [];
        $sub = collect($package['sub_industries'] ?? [])->firstWhere('slug', $subIndustry);

        return [
            'sub_industry' => $subIndustry,
            'enabled_modules' => $sub['modules'] ?? ($package['features'] ?? []),
            'traceability_enabled' => in_array('Traceability', $sub['modules'] ?? ($package['features'] ?? []), true),
            'finance_integration' => true,
            'inventory_integration' => true,
            'weather_api_ready' => true,
            'iot_ready' => true,
        ];
    }

    private function printingBrandingConfiguration(?string $subIndustry): array
    {
        $package = file_exists(base_path('Modules/PrintingBranding/module.php')) ? require base_path('Modules/PrintingBranding/module.php') : [];
        $sub = collect($package['sub_industries'] ?? [])->firstWhere('slug', $subIndustry);
        $enabled = $sub['modules'] ?? ($package['features'] ?? []);

        return [
            'sub_industry' => $subIndustry,
            'enabled_modules' => $enabled,
            'workflow' => $package['workflows'] ?? [],
            'communication_channels' => $package['communication_channels'] ?? [],
            'finance_integration' => true,
            'inventory_integration' => true,
            'procurement_integration' => true,
            'client_portal_approval_enabled' => true,
            'future_integrations_ready' => true,
        ];
    }

    private function tenantColumns(array $attributes): array
    {
        return collect($attributes)
            ->filter(fn ($value, $column) => Schema::hasColumn('tenants', $column))
            ->all();
    }
}
