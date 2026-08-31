<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\IamPermission;
use App\Models\IamRole;
use App\Models\SecuritySetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\ActiveBusiness;
use App\Support\SchemaCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IamService
{
    private static array $bootstrappedBusinesses = [];

    private static array $permissionCache = [];

    private static ?bool $ready = null;

    public const PERMISSIONS = [
        'administration.view', 'users.view', 'users.create', 'users.edit', 'users.deactivate',
        'roles.manage', 'permissions.manage', 'branches.manage', 'teams.manage', 'approvals.manage',
        'security.manage', 'audit.view', 'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
        'projects.view', 'projects.create', 'projects.edit', 'projects.close', 'finance.view',
        'finance.coa.manage', 'finance.gl.view', 'finance.gl.post', 'finance.gl.reverse', 'finance.gl.unreverse',
        'finance.ar.view', 'finance.ar.manage', 'finance.ap.view', 'finance.ap.approve', 'finance.ap.manage',
        'finance.banking.manage', 'finance.reconciliation.manage', 'finance.assets.manage',
        'finance.periods.manage', 'finance.reports.view', 'expenses.view', 'expenses.manage',
        'accounting.expenses.view', 'accounting.expenses.manage', 'letters.view', 'letters.create', 'letters.edit', 'letters.delete',
        'inventory.view', 'inventory.adjust', 'reports.view', 'reports.export',
        'hospitality.view', 'hospitality.manage', 'hospitality.reports',
        'hospitality.reservations.view', 'hospitality.reservations.manage',
        'hospitality.rooms.view', 'hospitality.rooms.manage',
        'hospitality.guests.view', 'hospitality.guests.manage',
        'hospitality.checkins.manage', 'hospitality.checkouts.manage',
        'hospitality.housekeeping.view', 'hospitality.housekeeping.manage',
        'hospitality.maintenance.view', 'hospitality.maintenance.manage',
        'hospitality.restaurant.view', 'hospitality.restaurant.manage',
        'hospitality.events.view', 'hospitality.events.manage',
        'hospitality.billing.manage', 'hospitality.finance.post',
        'fitness.view', 'fitness.manage', 'fitness.reports',
        'fitness.memberships.view', 'fitness.memberships.create', 'fitness.memberships.edit', 'fitness.memberships.delete',
        'fitness.members.view', 'fitness.members.create', 'fitness.members.edit', 'fitness.members.delete',
        'fitness.trainers.view', 'fitness.trainers.manage',
        'fitness.attendance.view', 'fitness.attendance.manage',
        'fitness.classes.view', 'fitness.classes.manage',
        'fitness.programs.view', 'fitness.programs.manage',
        'fitness.exercises.view', 'fitness.exercises.manage',
        'fitness.health.view', 'fitness.health.manage',
        'fitness.assessments.view', 'fitness.assessments.manage',
        'fitness.personal-training.view', 'fitness.personal-training.manage',
        'fitness.nutrition.view', 'fitness.nutrition.manage',
        'fitness.challenges.view', 'fitness.challenges.manage',
        'fitness.equipment.view', 'fitness.equipment.manage',
        'fitness.payments.view', 'fitness.payments.manage',
        'retail.view', 'retail.manage', 'retail.reports',
        'retail.pos.view', 'retail.pos.manage',
        'retail.products.view', 'retail.products.manage',
        'retail.inventory.view', 'retail.inventory.manage',
        'retail.warehousing.view', 'retail.warehousing.manage',
        'retail.orders.view', 'retail.orders.manage',
        'retail.customers.view', 'retail.customers.manage',
        'retail.loyalty.view', 'retail.loyalty.manage',
        'retail.promotions.view', 'retail.promotions.manage',
        'retail.gift-cards.view', 'retail.gift-cards.manage',
        'retail.returns.view', 'retail.returns.manage',
        'retail.procurement.view', 'retail.procurement.manage',
        'retail.suppliers.view', 'retail.suppliers.manage',
        'retail.branches.view', 'retail.branches.manage',
        'retail.ecommerce.view', 'retail.ecommerce.manage',
        'retail.analytics.view', 'retail.settings.manage',
        'retail.scanning.view', 'retail.scanning.manage', 'retail.scanning.self-checkout',
        'retail.scanning.reports', 'retail.scanning.override', 'retail.scanning.compliance',
        'real-estate.view', 'real-estate.manage', 'real-estate.reports',
        'real-estate.properties.view', 'real-estate.properties.manage',
        'real-estate.units.view', 'real-estate.units.manage',
        'real-estate.tenants.view', 'real-estate.tenants.manage', 'real-estate.tenants.offboard', 'real-estate.tenants.delete',
        'real-estate.leases.view', 'real-estate.leases.manage',
        'real-estate.billing.view', 'real-estate.billing.manage',
        'real-estate.listings.view', 'real-estate.listings.manage', 'real-estate.listings.approve',
        'real-estate.buyers.view', 'real-estate.buyers.manage',
        'real-estate.sales.view', 'real-estate.sales.manage',
        'real-estate.agents.view', 'real-estate.agents.manage',
        'real-estate.commissions.view', 'real-estate.commissions.manage', 'real-estate.commissions.approve',
        'real-estate.maintenance.view', 'real-estate.maintenance.manage',
        'real-estate.service-requests.view', 'real-estate.service-requests.manage',
        'real-estate.inspections.view', 'real-estate.inspections.manage',
        'real-estate.valuations.view', 'real-estate.valuations.manage',
        'real-estate.land.view', 'real-estate.land.manage',
        'real-estate.development.view', 'real-estate.development.manage',
        'real-estate.utilities.view', 'real-estate.utilities.manage',
        'real-estate.amenities.view', 'real-estate.amenities.manage',
        'real-estate.tenant-ledger.view', 'real-estate.tenant-ledger.manage',
        'real-estate.documents.view', 'real-estate.documents.manage',
        'agriculture.view', 'agriculture.manage', 'agriculture.reports', 'agriculture.settings',
        'farms.manage', 'fields.manage', 'crops.manage', 'crop_plans.manage', 'farm_activities.manage',
        'harvests.manage', 'livestock.manage', 'veterinary.manage', 'breeding.manage', 'inputs.manage',
        'equipment.manage', 'agriculture.procurement', 'agriculture.finance', 'agriculture.documents.manage',
        'construction.view', 'construction.dashboard',
        'boq.view', 'boq.create', 'boq.update', 'boq.approve',
        'estimates.manage', 'tenders.manage', 'projects.manage', 'sites.manage',
        'site_reports.create', 'materials.manage', 'material_requests.create', 'material_requests.approve',
        'construction.procurement', 'contractors.manage', 'subcontracts.manage',
        'measurements.create', 'measurements.approve', 'certificates.create', 'certificates.approve',
        'variations.create', 'variations.approve', 'rfi.manage', 'site_instructions.manage',
        'quality.manage', 'safety.manage', 'defects.manage', 'handover.manage',
        'construction.finance', 'construction.reports', 'construction.settings',
        'printing.view', 'printing.dashboard',
        'estimates.view', 'estimates.create', 'estimates.approve',
        'production_jobs.view', 'production_jobs.create', 'production_jobs.update', 'production_jobs.approve',
        'artwork.view', 'artwork.manage', 'artwork.approve',
        'production.schedule', 'production.execute', 'production.quality_control',
        'machines.manage', 'inventory.consume', 'dispatch.manage',
        'job_costing.view', 'job_costing.manage',
        'printing_reports.view', 'printing_settings.manage',
        'automotive.view', 'automotive.dashboard',
        'vehicles.view', 'vehicles.create', 'vehicles.update',
        'bookings.manage', 'checkin.manage',
        'inspections.create', 'inspections.approve',
        'job_cards.view', 'job_cards.create', 'job_cards.update', 'job_cards.assign', 'job_cards.complete',
        'parts.view', 'parts.issue', 'parts.return',
        'automotive.inventory', 'automotive.procurement',
        'technicians.manage', 'workshop.manage', 'quality_control.manage',
        'warranty.manage', 'fleet.manage', 'vehicle_sales.manage',
        'automotive.finance', 'automotive.reports', 'automotive.settings',
        'salon.view', 'salon.manage', 'salon.reports',
        'salon.appointments.view', 'salon.appointments.manage',
        'salon.staff.view', 'salon.staff.manage',
        'salon.services.view', 'salon.services.manage',
        'salon.pos.view', 'salon.pos.manage',
        'salon.memberships.view', 'salon.memberships.manage',
        'salon.loyalty.view', 'salon.loyalty.manage',
        'salon.consultations.view', 'salon.consultations.manage',
        'salon.treatments.view', 'salon.treatments.manage',
        'salon.inventory.view', 'salon.inventory.manage',
        'salon.commissions.view', 'salon.commissions.manage',
        'salon.wellness.view', 'salon.wellness.manage',
        'etims.view', 'etims.manage', 'etims.reports', 'etims.retry',
        'communication.view', 'communication.send', 'communication.create_group', 'communication.manage_group',
        'communication.create_channel', 'communication.manage_channel', 'communication.upload', 'communication.delete_own',
        'communication.moderate', 'communication.announcements.create', 'communication.announcements.manage',
        'communication.mass_mention', 'communication.audit', 'communication.settings',
        'communication.manage', 'communication.admin', 'communication.announce', 'communication.reports',
    ];

    public const ROLES = [
        'system-administrator' => 'System Administrator',
        'business-administrator' => 'Business Administrator',
        'finance-manager' => 'Finance Manager',
        'accountant' => 'Accountant',
        'procurement-officer' => 'Procurement Officer',
        'project-manager' => 'Project Manager',
        'technician' => 'Technician',
        'sales-executive' => 'Sales Executive',
        'hr-manager' => 'HR Manager',
        'operations-manager' => 'Operations Manager',
        'store-manager' => 'Store Manager',
        'hotel-manager' => 'Hotel Manager',
        'front-desk-officer' => 'Front Desk Officer',
        'reservations-officer' => 'Reservations Officer',
        'housekeeping-supervisor' => 'Housekeeping Supervisor',
        'housekeeping-staff' => 'Housekeeping Staff',
        'maintenance-officer' => 'Maintenance Officer',
        'restaurant-manager' => 'Restaurant Manager',
        'restaurant-staff' => 'Restaurant Staff',
        'events-coordinator' => 'Events Coordinator',
        'finance-officer' => 'Finance Officer',
        'gym-owner' => 'Gym Owner',
        'gym-manager' => 'Gym Manager',
        'trainer' => 'Trainer',
        'receptionist' => 'Receptionist',
        'gym-member' => 'Member',
        'director' => 'Director',
        'retail-director' => 'Retail Director',
        'branch-manager' => 'Branch Manager',
        'cashier' => 'Cashier',
        'warehouse-manager' => 'Warehouse Manager',
        'warehouse-staff' => 'Warehouse Staff',
        'customer-service' => 'Customer Service',
        'retail-accountant' => 'Retail Accountant',
        'retail-auditor' => 'Retail Auditor',
        'real-estate-director' => 'Real Estate Director',
        'real-estate-branch-manager' => 'Real Estate Branch Manager',
        'property-manager' => 'Property Manager',
        'leasing-officer' => 'Leasing Officer',
        'sales-agent' => 'Sales Agent',
        'property-agent' => 'Property Agent',
        'maintenance-manager' => 'Maintenance Manager',
        'inspector' => 'Inspector',
        'valuer' => 'Valuer',
        'agriculture-administrator' => 'Agriculture Administrator',
        'farm-manager' => 'Farm Manager',
        'farm-supervisor' => 'Farm Supervisor',
        'agronomist' => 'Agronomist',
        'livestock-manager' => 'Livestock Manager',
        'veterinarian' => 'Veterinarian',
        'field-officer' => 'Field Officer',
        'store-keeper' => 'Store Keeper',
        'equipment-manager' => 'Equipment Manager',
        'farm-accountant' => 'Farm Accountant',
        'farm-worker' => 'Farm Worker',
        'agriculture-viewer' => 'Agriculture Viewer',
        'construction-administrator' => 'Construction Administrator',
        'project-director' => 'Project Director',
        'construction-manager' => 'Construction Manager',
        'site-manager' => 'Site Manager',
        'site-engineer' => 'Site Engineer',
        'quantity-surveyor' => 'Quantity Surveyor',
        'planner' => 'Planner',
        'safety-officer' => 'Safety Officer',
        'quality-officer' => 'Quality Officer',
        'foreman' => 'Foreman',
        'construction-equipment-manager' => 'Equipment Manager',
        'printing-administrator' => 'Printing Administrator',
        'managing-director' => 'Managing Director',
        'printing-sales-manager' => 'Sales Manager',
        'printing-sales-executive' => 'Sales Executive',
        'estimator' => 'Estimator',
        'graphic-designer' => 'Graphic Designer',
        'prepress-operator' => 'Prepress Operator',
        'production-manager' => 'Production Manager',
        'machine-operator' => 'Machine Operator',
        'finishing-operator' => 'Finishing Operator',
        'quality-controller' => 'Quality Controller',
        'printing-store-manager' => 'Store Manager',
        'printing-procurement-officer' => 'Procurement Officer',
        'dispatch-officer' => 'Dispatch Officer',
        'printing-finance-officer' => 'Finance Officer',
        'printing-accountant' => 'Accountant',
        'automotive-administrator' => 'Automotive Administrator',
        'automotive-branch-manager' => 'Automotive Branch Manager',
        'workshop-manager' => 'Workshop Manager',
        'service-manager' => 'Service Manager',
        'service-advisor' => 'Service Advisor',
        'workshop-supervisor' => 'Workshop Supervisor',
        'automotive-technician' => 'Technician',
        'master-technician' => 'Master Technician',
        'diagnostic-technician' => 'Diagnostic Technician',
        'auto-electrician' => 'Auto Electrician',
        'body-repair-technician' => 'Body Repair Technician',
        'painter' => 'Painter',
        'tyre-technician' => 'Tyre Technician',
        'automotive-quality-controller' => 'Quality Controller',
        'parts-manager' => 'Parts Manager',
        'automotive-store-keeper' => 'Store Keeper',
        'automotive-procurement-officer' => 'Procurement Officer',
        'automotive-sales-manager' => 'Sales Manager',
        'automotive-salesperson' => 'Salesperson',
        'fleet-manager' => 'Fleet Manager',
        'recovery-driver' => 'Recovery Driver',
        'automotive-finance-manager' => 'Finance Manager',
        'automotive-accountant' => 'Accountant',
        'automotive-viewer' => 'Automotive Viewer',
        'salon-owner' => 'Salon Owner',
        'salon-manager' => 'Salon Manager',
        'salon-receptionist' => 'Salon Receptionist',
        'stylist' => 'Stylist',
        'spa-therapist' => 'Spa Therapist',
        'salon-cashier' => 'Salon Cashier',
        'salon-inventory-clerk' => 'Salon Inventory Clerk',
        'salon-branch-manager' => 'Salon Branch Manager',
        'wellness-consultant' => 'Wellness Consultant',
        'salon-finance-officer' => 'Salon Finance Officer',
        'viewer' => 'Viewer',
    ];

    public function bootstrapBusinessDefaults(?User $owner = null): void
    {
        if (! $this->ready()) {
            return;
        }

        self::$permissionCache = [];

        foreach (self::PERMISSIONS as $name) {
            IamPermission::firstOrCreate(['name' => $name], ['module' => Str::before($name, '.')]);
        }

        foreach (self::ROLES as $slug => $name) {
            $role = IamRole::firstOrCreate(
                ['business_id' => ActiveBusiness::id(), 'slug' => $slug],
                ['name' => $name, 'is_system' => true]
            );

            if (in_array($slug, ['system-administrator', 'business-administrator'], true)) {
                $role->permissions()->sync(IamPermission::pluck('id'));
            } elseif (in_array($slug, ['finance-manager', 'director'], true)) {
                $role->permissions()->syncWithoutDetaching(IamPermission::where('name', 'finance.gl.unreverse')->pluck('id'));
            }
        }

        $this->syncFinanceRolePermissions();
        $this->syncHospitalityRolePermissions();
        $this->syncFitnessRolePermissions();
        $this->syncRetailRolePermissions();
        $this->syncRealEstateRolePermissions();
        $this->syncAgricultureRolePermissions();
        $this->syncConstructionRolePermissions();
        $this->syncPrintingRolePermissions();
        $this->syncAutomotiveRolePermissions();
        $this->syncSalonRolePermissions();
        $this->syncCommunicationRolePermissions();

        if (SchemaCache::hasTable('security_settings')) {
            SecuritySetting::firstOrCreate(['business_id' => ActiveBusiness::id()]);
        }

        if ($owner && SchemaCache::hasTable('business_user')) {
            DB::table('business_user')->insertOrIgnore([
                'business_id' => ActiveBusiness::id(),
                'user_id' => $owner->id,
                'iam_role_id' => IamRole::where('business_id', ActiveBusiness::id())->where('slug', 'system-administrator')->value('id'),
                'status' => 'Active',
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    public function bootstrap(): void
    {
        if (! $this->ready()) {
            return;
        }

        self::$permissionCache = [];

        $businessId = ActiveBusiness::id();
        if (
            $businessId
            && (self::$bootstrappedBusinesses[$businessId] ?? false)
            && $this->businessPermissionsAreCurrent($businessId)
        ) {
            return;
        }

        foreach (self::PERMISSIONS as $name) {
            IamPermission::firstOrCreate(['name' => $name], ['module' => Str::before($name, '.')]);
        }

        foreach (self::ROLES as $slug => $name) {
            $role = IamRole::firstOrCreate(
                ['business_id' => ActiveBusiness::id(), 'slug' => $slug],
                ['name' => $name, 'is_system' => true]
            );

            if (in_array($slug, ['system-administrator', 'business-administrator'], true)) {
                $role->permissions()->sync(IamPermission::pluck('id'));
            } elseif (in_array($slug, ['finance-manager', 'director'], true)) {
                $role->permissions()->syncWithoutDetaching(IamPermission::where('name', 'finance.gl.unreverse')->pluck('id'));
            }
        }

        $this->syncFinanceRolePermissions();
        $this->syncHospitalityRolePermissions();
        $this->syncFitnessRolePermissions();
        $this->syncRetailRolePermissions();
        $this->syncRealEstateRolePermissions();
        $this->syncAgricultureRolePermissions();
        $this->syncConstructionRolePermissions();
        $this->syncPrintingRolePermissions();
        $this->syncAutomotiveRolePermissions();
        $this->syncSalonRolePermissions();
        $this->syncCommunicationRolePermissions();

        if (SchemaCache::hasTable('security_settings')) {
            SecuritySetting::firstOrCreate(['business_id' => ActiveBusiness::id()]);
        }

        if (auth()->check() && ! in_array(auth()->user()->role, ['client_portal', 'super_admin'], true)) {
            DB::table('business_user')->insertOrIgnore([
                'business_id' => ActiveBusiness::id(),
                'user_id' => auth()->id(),
                'iam_role_id' => IamRole::where('business_id', ActiveBusiness::id())->where('slug', 'system-administrator')->value('id'),
                'status' => 'Active',
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        if ($businessId) {
            self::$bootstrappedBusinesses[$businessId] = true;
        }
    }

    public function permissions(User $user): array
    {
        if ($user->role === 'super_admin') {
            return self::PERMISSIONS;
        }

        if (! $this->ready() || $user->role === 'client_portal') {
            return [];
        }

        $cacheKey = ActiveBusiness::id().':'.$user->id;
        if (array_key_exists($cacheKey, self::$permissionCache)) {
            return self::$permissionCache[$cacheKey];
        }

        $roleId = DB::table('business_user')
            ->where('business_id', ActiveBusiness::id())
            ->where('user_id', $user->id)
            ->where('status', 'Active')
            ->value('iam_role_id');

        if ($user->role === 'admin' && ! $roleId) {
            return self::PERMISSIONS;
        }

        $role = IamRole::find($roleId)?->permissions()->pluck('name')->all() ?? [];
        $direct = SchemaCache::hasTable('iam_permission_user')
            ? DB::table('iam_permission_user')
                ->join('iam_permissions', 'iam_permissions.id', '=', 'iam_permission_user.iam_permission_id')
                ->where('user_id', $user->id)
                ->pluck('name')
                ->all()
            : [];

        return self::$permissionCache[$cacheKey] = array_values(array_unique(array_merge($role, $direct)));
    }

    public function can(User $user, string $permission): bool
    {
        return in_array($permission, $this->permissions($user), true);
    }

    public function audit(string $event, $subject = null, array $old = []): void
    {
        if (! SchemaCache::hasTable('admin_audit_logs')) {
            return;
        }

        AdminAuditLog::create([
            'business_id' => ActiveBusiness::id(),
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $subject?->getAttributes(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function recordLogin(Request $request, ?User $user, bool $success, string $event = 'login'): void
    {
        if (! SchemaCache::hasTable('login_activities')) {
            return;
        }

        DB::table('login_activities')->insert([
            'user_id' => $user?->id,
            'email' => $user?->email ?? $request->input('username'),
            'event' => $event,
            'successful' => $success,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $this->deviceName($request->userAgent()),
            'browser' => $this->browser($request->userAgent()),
            'operating_system' => $this->os($request->userAgent()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($success && $user && SchemaCache::hasTable('user_devices')) {
            $fingerprint = hash('sha256', $request->userAgent().'|'.$request->ip());
            UserDevice::updateOrCreate(
                ['user_id' => $user->id, 'fingerprint' => $fingerprint],
                [
                    'name' => $this->deviceName($request->userAgent()),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'last_activity_at' => now(),
                    'revoked_at' => null,
                ]
            );
        }
    }

    private function ready(): bool
    {
        return self::$ready ??= SchemaCache::hasTable('iam_permissions')
            && SchemaCache::hasTable('iam_roles')
            && SchemaCache::hasTable('iam_permission_role')
            && SchemaCache::hasTable('business_user');
    }

    private function businessPermissionsAreCurrent(int $businessId): bool
    {
        if (IamRole::where('business_id', $businessId)->whereIn('slug', ['system-administrator', 'viewer'])->count() !== 2) {
            return false;
        }

        $permissionCount = IamPermission::whereIn('name', self::PERMISSIONS)->count();
        if ($permissionCount !== count(self::PERMISSIONS)) {
            return false;
        }

        $systemAdministrator = IamRole::where('business_id', $businessId)->where('slug', 'system-administrator')->first();

        return $systemAdministrator
            && $systemAdministrator->permissions()->whereIn('name', self::PERMISSIONS)->count() === count(self::PERMISSIONS);
    }

    private function syncFinanceRolePermissions(): void
    {
        $manager = [
            'finance.view', 'finance.coa.manage', 'finance.gl.view', 'finance.gl.post',
            'finance.gl.reverse', 'finance.gl.unreverse', 'finance.ar.view', 'finance.ar.manage',
            'finance.ap.view', 'finance.ap.approve', 'finance.ap.manage', 'finance.banking.manage',
            'finance.reconciliation.manage', 'finance.assets.manage', 'finance.periods.manage',
            'finance.reports.view', 'expenses.view', 'expenses.manage',
            'accounting.expenses.view', 'accounting.expenses.manage', 'reports.view', 'reports.export',
        ];

        $map = [
            'finance-manager' => $manager,
            'accountant' => [
                'finance.view', 'finance.gl.view', 'finance.gl.post', 'finance.ar.view',
                'finance.ar.manage', 'finance.ap.view', 'finance.ap.manage', 'finance.banking.manage',
                'finance.reconciliation.manage', 'finance.reports.view', 'expenses.view', 'expenses.manage',
                'accounting.expenses.view', 'accounting.expenses.manage', 'reports.view', 'reports.export',
            ],
            'finance-officer' => [
                'finance.view', 'finance.gl.view', 'finance.gl.post', 'finance.ar.view',
                'finance.ar.manage', 'finance.ap.view', 'finance.banking.manage', 'expenses.view', 'expenses.manage',
                'accounting.expenses.view', 'accounting.expenses.manage',
            ],
            'director' => ['finance.view', 'finance.gl.view', 'finance.ar.view', 'finance.ap.view', 'finance.reports.view', 'expenses.view', 'accounting.expenses.view', 'reports.view', 'reports.export'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncHospitalityRolePermissions(): void
    {
        $map = [
            'hotel-manager' => ['hospitality.view', 'hospitality.manage', 'hospitality.reports'],
            'front-desk-officer' => ['hospitality.view', 'hospitality.reservations.manage', 'hospitality.checkins.manage', 'hospitality.checkouts.manage'],
            'reservations-officer' => ['hospitality.view', 'hospitality.reservations.manage'],
            'housekeeping-supervisor' => ['hospitality.view', 'hospitality.housekeeping.manage'],
            'housekeeping-staff' => ['hospitality.housekeeping.view'],
            'maintenance-officer' => ['hospitality.maintenance.manage'],
            'restaurant-manager' => ['hospitality.restaurant.manage'],
            'restaurant-staff' => ['hospitality.restaurant.view'],
            'events-coordinator' => ['hospitality.events.manage'],
            'finance-officer' => ['hospitality.billing.manage', 'hospitality.finance.post'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncFitnessRolePermissions(): void
    {
        $map = [
            'gym-owner' => ['fitness.view', 'fitness.manage', 'fitness.reports', 'fitness.memberships.view', 'fitness.memberships.create', 'fitness.memberships.edit', 'fitness.memberships.delete', 'fitness.members.view', 'fitness.members.create', 'fitness.members.edit', 'fitness.members.delete', 'fitness.trainers.view', 'fitness.trainers.manage', 'fitness.attendance.view', 'fitness.attendance.manage', 'fitness.classes.view', 'fitness.classes.manage', 'fitness.programs.view', 'fitness.programs.manage', 'fitness.exercises.view', 'fitness.exercises.manage', 'fitness.health.view', 'fitness.health.manage', 'fitness.assessments.view', 'fitness.assessments.manage', 'fitness.personal-training.view', 'fitness.personal-training.manage', 'fitness.nutrition.view', 'fitness.nutrition.manage', 'fitness.challenges.view', 'fitness.challenges.manage', 'fitness.equipment.view', 'fitness.equipment.manage', 'fitness.payments.view', 'fitness.payments.manage'],
            'gym-manager' => ['fitness.view', 'fitness.manage', 'fitness.reports', 'fitness.memberships.view', 'fitness.memberships.create', 'fitness.memberships.edit', 'fitness.members.view', 'fitness.members.create', 'fitness.members.edit', 'fitness.trainers.view', 'fitness.trainers.manage', 'fitness.attendance.view', 'fitness.attendance.manage', 'fitness.classes.view', 'fitness.classes.manage', 'fitness.programs.view', 'fitness.programs.manage', 'fitness.exercises.view', 'fitness.exercises.manage', 'fitness.health.view', 'fitness.health.manage', 'fitness.assessments.view', 'fitness.assessments.manage', 'fitness.personal-training.view', 'fitness.personal-training.manage', 'fitness.nutrition.view', 'fitness.nutrition.manage', 'fitness.challenges.view', 'fitness.challenges.manage', 'fitness.equipment.view', 'fitness.equipment.manage', 'fitness.payments.view', 'fitness.payments.manage'],
            'trainer' => ['fitness.view', 'fitness.members.view', 'fitness.memberships.view', 'fitness.trainers.view', 'fitness.attendance.view', 'fitness.classes.view', 'fitness.classes.manage', 'fitness.programs.view', 'fitness.programs.manage', 'fitness.exercises.view', 'fitness.exercises.manage', 'fitness.health.view', 'fitness.health.manage', 'fitness.assessments.view', 'fitness.assessments.manage', 'fitness.personal-training.view', 'fitness.personal-training.manage', 'fitness.nutrition.view', 'fitness.nutrition.manage', 'fitness.challenges.view'],
            'receptionist' => ['fitness.view', 'fitness.memberships.view', 'fitness.memberships.create', 'fitness.members.view', 'fitness.members.create', 'fitness.members.edit', 'fitness.attendance.view', 'fitness.attendance.manage', 'fitness.classes.view', 'fitness.classes.manage', 'fitness.payments.view', 'fitness.payments.manage'],
            'gym-member' => ['fitness.view', 'fitness.members.view', 'fitness.memberships.view', 'fitness.attendance.view', 'fitness.classes.view', 'fitness.programs.view', 'fitness.exercises.view', 'fitness.health.view', 'fitness.assessments.view', 'fitness.personal-training.view', 'fitness.nutrition.view', 'fitness.challenges.view'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncRetailRolePermissions(): void
    {
        $map = [
            'retail-director' => ['retail.view', 'retail.manage', 'retail.reports', 'retail.analytics.view', 'retail.pos.view', 'retail.products.view', 'retail.inventory.view', 'retail.warehousing.view', 'retail.orders.view', 'retail.customers.view', 'retail.loyalty.view', 'retail.promotions.view', 'retail.gift-cards.view', 'retail.returns.view', 'retail.procurement.view', 'retail.suppliers.view', 'retail.branches.view', 'retail.ecommerce.view', 'retail.scanning.view', 'retail.scanning.manage', 'retail.scanning.self-checkout', 'retail.scanning.reports', 'retail.scanning.override', 'retail.scanning.compliance', 'etims.view', 'etims.manage', 'etims.reports', 'etims.retry'],
            'store-manager' => ['retail.view', 'retail.pos.manage', 'retail.products.manage', 'retail.inventory.manage', 'retail.customers.manage', 'retail.loyalty.manage', 'retail.promotions.view', 'retail.gift-cards.manage', 'retail.returns.manage', 'retail.reports', 'retail.scanning.view', 'retail.scanning.manage', 'retail.scanning.self-checkout', 'retail.scanning.override', 'retail.scanning.compliance'],
            'branch-manager' => ['retail.view', 'retail.pos.view', 'retail.inventory.view', 'retail.orders.manage', 'retail.customers.manage', 'retail.branches.view', 'retail.reports', 'retail.scanning.view', 'retail.scanning.manage', 'retail.scanning.reports'],
            'cashier' => ['retail.view', 'retail.pos.manage', 'retail.products.view', 'retail.customers.view', 'retail.loyalty.view', 'retail.gift-cards.view', 'retail.returns.view', 'retail.scanning.view', 'retail.scanning.manage', 'retail.scanning.self-checkout'],
            'warehouse-manager' => ['retail.view', 'retail.inventory.manage', 'retail.warehousing.manage', 'retail.orders.view', 'retail.procurement.view', 'retail.reports', 'retail.scanning.view', 'retail.scanning.compliance', 'retail.scanning.reports'],
            'warehouse-staff' => ['retail.view', 'retail.inventory.view', 'retail.warehousing.view', 'retail.orders.view', 'retail.scanning.view'],
            'customer-service' => ['retail.view', 'retail.customers.manage', 'retail.loyalty.view', 'retail.returns.manage', 'retail.orders.view'],
            'retail-accountant' => ['retail.view', 'retail.reports', 'retail.pos.view', 'retail.returns.view', 'finance.view', 'finance.gl.view', 'retail.scanning.reports', 'etims.view', 'etims.reports'],
            'retail-auditor' => ['retail.view', 'retail.reports', 'retail.analytics.view', 'audit.view', 'retail.scanning.view', 'retail.scanning.reports', 'retail.scanning.compliance', 'etims.view', 'etims.reports'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncAgricultureRolePermissions(): void
    {
        $all = [
            'agriculture.view', 'agriculture.manage', 'agriculture.reports', 'agriculture.settings',
            'farms.manage', 'fields.manage', 'crops.manage', 'crop_plans.manage', 'farm_activities.manage',
            'harvests.manage', 'livestock.manage', 'veterinary.manage', 'breeding.manage', 'inputs.manage',
            'equipment.manage', 'agriculture.procurement', 'agriculture.finance', 'agriculture.documents.manage',
            'finance.view', 'finance.ar.view', 'finance.reports.view', 'reports.view', 'reports.export',
            'documents.view', 'communication.view',
        ];

        $map = [
            'agriculture-administrator' => $all,
            'farm-manager' => ['agriculture.view', 'agriculture.manage', 'agriculture.reports', 'farms.manage', 'fields.manage', 'crops.manage', 'crop_plans.manage', 'farm_activities.manage', 'harvests.manage', 'inputs.manage', 'equipment.manage', 'agriculture.procurement', 'agriculture.documents.manage'],
            'farm-supervisor' => ['agriculture.view', 'farms.manage', 'fields.manage', 'crop_plans.manage', 'farm_activities.manage', 'harvests.manage', 'inputs.manage', 'equipment.manage'],
            'agronomist' => ['agriculture.view', 'fields.manage', 'crops.manage', 'crop_plans.manage', 'farm_activities.manage', 'inputs.manage', 'harvests.manage', 'agriculture.reports'],
            'livestock-manager' => ['agriculture.view', 'livestock.manage', 'veterinary.manage', 'breeding.manage', 'inputs.manage', 'agriculture.reports'],
            'veterinarian' => ['agriculture.view', 'livestock.manage', 'veterinary.manage', 'breeding.manage'],
            'field-officer' => ['agriculture.view', 'fields.manage', 'farm_activities.manage', 'harvests.manage', 'inputs.manage'],
            'store-keeper' => ['agriculture.view', 'inputs.manage', 'harvests.manage', 'agriculture.procurement'],
            'equipment-manager' => ['agriculture.view', 'equipment.manage', 'farm_activities.manage'],
            'farm-accountant' => ['agriculture.view', 'agriculture.finance', 'agriculture.reports', 'finance.view', 'finance.ar.view', 'finance.reports.view'],
            'farm-worker' => ['agriculture.view', 'farm_activities.manage', 'harvests.manage'],
            'agriculture-viewer' => ['agriculture.view', 'agriculture.reports'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncRealEstateRolePermissions(): void
    {
        $all = [
            'real-estate.view', 'real-estate.manage', 'real-estate.reports',
            'real-estate.properties.view', 'real-estate.properties.manage',
            'real-estate.units.view', 'real-estate.units.manage',
            'real-estate.tenants.view', 'real-estate.tenants.manage', 'real-estate.tenants.offboard', 'real-estate.tenants.delete',
            'real-estate.leases.view', 'real-estate.leases.manage',
            'real-estate.billing.view', 'real-estate.billing.manage',
            'real-estate.listings.view', 'real-estate.listings.manage', 'real-estate.listings.approve',
            'real-estate.buyers.view', 'real-estate.buyers.manage',
            'real-estate.sales.view', 'real-estate.sales.manage',
            'real-estate.agents.view', 'real-estate.agents.manage',
            'real-estate.commissions.view', 'real-estate.commissions.manage', 'real-estate.commissions.approve',
            'real-estate.maintenance.view', 'real-estate.maintenance.manage',
            'real-estate.service-requests.view', 'real-estate.service-requests.manage',
            'real-estate.inspections.view', 'real-estate.inspections.manage',
            'real-estate.valuations.view', 'real-estate.valuations.manage',
            'real-estate.land.view', 'real-estate.land.manage',
            'real-estate.development.view', 'real-estate.development.manage',
            'real-estate.utilities.view', 'real-estate.utilities.manage',
            'real-estate.amenities.view', 'real-estate.amenities.manage',
            'real-estate.tenant-ledger.view', 'real-estate.tenant-ledger.manage',
            'real-estate.documents.view', 'real-estate.documents.manage',
            'finance.view', 'finance.ar.view', 'finance.reports.view', 'reports.view', 'reports.export',
            'communication.view', 'documents.view', 'etims.view',
        ];

        $map = [
            'real-estate-director' => $all,
            'real-estate-branch-manager' => array_diff($all, ['real-estate.commissions.approve', 'real-estate.tenants.delete']),
            'property-manager' => ['real-estate.view', 'real-estate.properties.view', 'real-estate.properties.manage', 'real-estate.units.view', 'real-estate.units.manage', 'real-estate.tenants.view', 'real-estate.tenants.offboard', 'real-estate.leases.view', 'real-estate.utilities.view', 'real-estate.amenities.view', 'real-estate.amenities.manage', 'real-estate.maintenance.view', 'real-estate.maintenance.manage', 'real-estate.inspections.view', 'real-estate.documents.view', 'real-estate.documents.manage', 'real-estate.reports'],
            'leasing-officer' => ['real-estate.view', 'real-estate.properties.view', 'real-estate.units.view', 'real-estate.tenants.view', 'real-estate.tenants.manage', 'real-estate.tenants.offboard', 'real-estate.leases.view', 'real-estate.leases.manage', 'real-estate.billing.view', 'real-estate.billing.manage', 'real-estate.utilities.view', 'real-estate.utilities.manage', 'real-estate.tenant-ledger.view', 'real-estate.documents.view', 'real-estate.documents.manage', 'finance.view', 'finance.ar.view'],
            'sales-agent' => ['real-estate.view', 'real-estate.properties.view', 'real-estate.units.view', 'real-estate.listings.view', 'real-estate.listings.manage', 'real-estate.buyers.view', 'real-estate.buyers.manage', 'real-estate.sales.view', 'real-estate.sales.manage', 'real-estate.commissions.view'],
            'property-agent' => ['real-estate.view', 'real-estate.properties.view', 'real-estate.units.view', 'real-estate.listings.view', 'real-estate.listings.manage', 'real-estate.tenants.view', 'real-estate.buyers.view'],
            'accountant' => ['real-estate.view', 'real-estate.tenants.view', 'real-estate.billing.view', 'real-estate.billing.manage', 'real-estate.utilities.view', 'real-estate.utilities.manage', 'real-estate.tenant-ledger.view', 'real-estate.tenant-ledger.manage', 'real-estate.amenities.view', 'real-estate.sales.view', 'real-estate.commissions.view', 'real-estate.reports', 'finance.view', 'finance.ar.view', 'finance.reports.view', 'etims.view'],
            'maintenance-manager' => ['real-estate.view', 'real-estate.maintenance.view', 'real-estate.maintenance.manage', 'real-estate.service-requests.view', 'real-estate.service-requests.manage', 'real-estate.properties.view', 'real-estate.units.view'],
            'technician' => ['real-estate.view', 'real-estate.maintenance.view', 'real-estate.maintenance.manage', 'real-estate.service-requests.view'],
            'inspector' => ['real-estate.view', 'real-estate.inspections.view', 'real-estate.inspections.manage', 'real-estate.properties.view', 'real-estate.units.view'],
            'valuer' => ['real-estate.view', 'real-estate.valuations.view', 'real-estate.valuations.manage', 'real-estate.properties.view', 'real-estate.land.view'],
            'customer-service' => ['real-estate.view', 'real-estate.tenants.view', 'real-estate.buyers.view', 'real-estate.service-requests.view', 'real-estate.service-requests.manage', 'real-estate.amenities.view', 'real-estate.amenities.manage', 'real-estate.tenant-ledger.view', 'communication.view'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncConstructionRolePermissions(): void
    {
        $all = [
            'construction.view', 'construction.dashboard',
            'boq.view', 'boq.create', 'boq.update', 'boq.approve',
            'estimates.manage', 'tenders.manage', 'projects.manage', 'sites.manage',
            'site_reports.create', 'materials.manage', 'material_requests.create', 'material_requests.approve',
            'construction.procurement', 'contractors.manage', 'subcontracts.manage',
            'measurements.create', 'measurements.approve', 'certificates.create', 'certificates.approve',
            'variations.create', 'variations.approve', 'rfi.manage', 'site_instructions.manage',
            'quality.manage', 'safety.manage', 'defects.manage', 'handover.manage',
            'construction.finance', 'construction.reports', 'construction.settings',
            'clients.view', 'clients.create', 'clients.edit', 'projects.view', 'projects.create', 'projects.edit',
            'inventory.view', 'inventory.adjust', 'finance.view', 'finance.ar.view', 'finance.reports.view',
            'reports.view', 'reports.export', 'documents.view', 'communication.view',
        ];

        $map = [
            'construction-administrator' => $all,
            'project-director' => array_values(array_unique(array_merge($all, ['finance.gl.view', 'audit.view']))),
            'project-manager' => ['construction.view', 'construction.dashboard', 'projects.manage', 'sites.manage', 'boq.view', 'tenders.manage', 'site_reports.create', 'materials.manage', 'material_requests.approve', 'contractors.manage', 'subcontracts.manage', 'measurements.approve', 'certificates.approve', 'variations.approve', 'rfi.manage', 'site_instructions.manage', 'quality.manage', 'safety.manage', 'defects.manage', 'construction.reports'],
            'construction-manager' => ['construction.view', 'construction.dashboard', 'projects.manage', 'sites.manage', 'materials.manage', 'contractors.manage', 'subcontracts.manage', 'measurements.approve', 'quality.manage', 'safety.manage', 'defects.manage', 'handover.manage', 'construction.reports'],
            'site-manager' => ['construction.view', 'sites.manage', 'site_reports.create', 'materials.manage', 'material_requests.create', 'rfi.manage', 'site_instructions.manage', 'quality.manage', 'safety.manage', 'defects.manage'],
            'site-engineer' => ['construction.view', 'sites.manage', 'site_reports.create', 'measurements.create', 'rfi.manage', 'site_instructions.manage', 'quality.manage', 'defects.manage'],
            'quantity-surveyor' => ['construction.view', 'boq.view', 'boq.create', 'boq.update', 'estimates.manage', 'measurements.create', 'measurements.approve', 'certificates.create', 'variations.create', 'construction.finance', 'construction.reports'],
            'estimator' => ['construction.view', 'boq.view', 'boq.create', 'boq.update', 'estimates.manage', 'tenders.manage'],
            'planner' => ['construction.view', 'projects.manage', 'sites.manage', 'construction.reports'],
            'procurement-officer' => ['construction.view', 'materials.manage', 'material_requests.approve', 'construction.procurement', 'contractors.manage'],
            'store-keeper' => ['construction.view', 'materials.manage', 'material_requests.create', 'material_requests.approve', 'inventory.view', 'inventory.adjust'],
            'safety-officer' => ['construction.view', 'safety.manage', 'site_reports.create', 'construction.reports'],
            'quality-officer' => ['construction.view', 'quality.manage', 'defects.manage', 'construction.reports'],
            'foreman' => ['construction.view', 'sites.manage', 'site_reports.create', 'material_requests.create', 'measurements.create', 'defects.manage'],
            'construction-equipment-manager' => ['construction.view', 'sites.manage', 'construction.reports'],
            'finance-manager' => ['construction.view', 'construction.finance', 'certificates.create', 'certificates.approve', 'construction.reports', 'finance.view', 'finance.ar.view', 'finance.reports.view'],
            'accountant' => ['construction.view', 'construction.finance', 'construction.reports', 'finance.view', 'finance.ar.view', 'finance.reports.view'],
            'viewer' => ['construction.view', 'construction.dashboard', 'boq.view', 'construction.reports'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncPrintingRolePermissions(): void
    {
        $all = [
            'printing.view', 'printing.dashboard',
            'estimates.view', 'estimates.create', 'estimates.approve',
            'production_jobs.view', 'production_jobs.create', 'production_jobs.update', 'production_jobs.approve',
            'artwork.view', 'artwork.manage', 'artwork.approve',
            'production.schedule', 'production.execute', 'production.quality_control',
            'machines.manage', 'inventory.consume', 'dispatch.manage',
            'job_costing.view', 'job_costing.manage',
            'printing_reports.view', 'printing_settings.manage',
            'clients.view', 'clients.create', 'clients.edit',
            'finance.view', 'finance.ar.view', 'finance.reports.view',
            'inventory.view', 'inventory.adjust',
            'reports.view', 'reports.export',
            'documents.view', 'communication.view',
        ];

        $map = [
            'printing-administrator' => $all,
            'managing-director' => array_values(array_unique(array_merge($all, ['finance.gl.view', 'audit.view']))),
            'printing-sales-manager' => ['printing.view', 'printing.dashboard', 'estimates.view', 'estimates.create', 'estimates.approve', 'production_jobs.view', 'production_jobs.create', 'artwork.view', 'dispatch.manage', 'job_costing.view', 'printing_reports.view', 'clients.view', 'clients.create', 'clients.edit', 'finance.view'],
            'printing-sales-executive' => ['printing.view', 'printing.dashboard', 'estimates.view', 'estimates.create', 'production_jobs.view', 'artwork.view', 'clients.view', 'clients.create'],
            'estimator' => ['printing.view', 'printing.dashboard', 'estimates.view', 'estimates.create', 'estimates.approve', 'production_jobs.view', 'job_costing.view'],
            'graphic-designer' => ['printing.view', 'production_jobs.view', 'artwork.view', 'artwork.manage', 'production.execute'],
            'prepress-operator' => ['printing.view', 'production_jobs.view', 'artwork.view', 'production.execute'],
            'production-manager' => ['printing.view', 'printing.dashboard', 'production_jobs.view', 'production_jobs.create', 'production_jobs.update', 'production_jobs.approve', 'production.schedule', 'production.execute', 'production.quality_control', 'machines.manage', 'inventory.consume', 'job_costing.view', 'printing_reports.view'],
            'machine-operator' => ['printing.view', 'production_jobs.view', 'production.execute', 'inventory.consume'],
            'finishing-operator' => ['printing.view', 'production_jobs.view', 'production.execute'],
            'quality-controller' => ['printing.view', 'production_jobs.view', 'production.quality_control', 'production.execute'],
            'printing-store-manager' => ['printing.view', 'production_jobs.view', 'inventory.view', 'inventory.adjust', 'inventory.consume', 'printing_reports.view'],
            'printing-procurement-officer' => ['printing.view', 'production_jobs.view', 'inventory.view', 'finance.ap.view'],
            'dispatch-officer' => ['printing.view', 'production_jobs.view', 'dispatch.manage'],
            'printing-finance-officer' => ['printing.view', 'production_jobs.view', 'job_costing.view', 'job_costing.manage', 'printing_reports.view', 'finance.view', 'finance.ar.view'],
            'printing-accountant' => ['printing.view', 'job_costing.view', 'job_costing.manage', 'printing_reports.view', 'finance.view', 'finance.gl.view', 'finance.reports.view'],
            'viewer' => ['printing.view', 'printing.dashboard', 'production_jobs.view', 'artwork.view', 'printing_reports.view'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncAutomotiveRolePermissions(): void
    {
        $all = [
            'automotive.view', 'automotive.dashboard',
            'vehicles.view', 'vehicles.create', 'vehicles.update',
            'bookings.manage', 'checkin.manage',
            'inspections.create', 'inspections.approve',
            'job_cards.view', 'job_cards.create', 'job_cards.update', 'job_cards.assign', 'job_cards.complete',
            'estimates.manage', 'estimates.approve',
            'parts.view', 'parts.issue', 'parts.return',
            'automotive.inventory', 'automotive.procurement',
            'technicians.manage', 'workshop.manage', 'quality_control.manage',
            'warranty.manage', 'fleet.manage', 'vehicle_sales.manage',
            'automotive.finance', 'automotive.reports', 'automotive.settings',
            'clients.view', 'clients.create', 'clients.edit',
            'inventory.view', 'inventory.adjust',
            'finance.view', 'finance.ar.view', 'finance.ap.view', 'finance.reports.view',
            'reports.view', 'reports.export', 'documents.view', 'communication.view',
        ];

        $workshop = [
            'automotive.view', 'automotive.dashboard', 'vehicles.view', 'vehicles.create', 'vehicles.update',
            'bookings.manage', 'checkin.manage', 'inspections.create', 'inspections.approve',
            'job_cards.view', 'job_cards.create', 'job_cards.update', 'job_cards.assign', 'job_cards.complete',
            'estimates.manage', 'parts.view', 'parts.issue', 'parts.return', 'workshop.manage',
            'quality_control.manage', 'warranty.manage', 'automotive.reports', 'clients.view',
        ];

        $technician = [
            'automotive.view', 'vehicles.view', 'inspections.create',
            'job_cards.view', 'job_cards.update', 'parts.view', 'parts.issue',
            'workshop.manage', 'quality_control.manage',
        ];

        $map = [
            'automotive-administrator' => $all,
            'automotive-branch-manager' => array_values(array_unique(array_merge($all, ['finance.gl.view', 'audit.view']))),
            'workshop-manager' => $workshop,
            'service-manager' => array_values(array_unique(array_merge($workshop, ['estimates.approve', 'automotive.finance']))),
            'service-advisor' => ['automotive.view', 'automotive.dashboard', 'clients.view', 'clients.create', 'clients.edit', 'vehicles.view', 'vehicles.create', 'vehicles.update', 'bookings.manage', 'checkin.manage', 'job_cards.view', 'job_cards.create', 'job_cards.update', 'estimates.manage', 'automotive.finance'],
            'workshop-supervisor' => ['automotive.view', 'automotive.dashboard', 'vehicles.view', 'inspections.create', 'job_cards.view', 'job_cards.update', 'job_cards.assign', 'parts.view', 'parts.issue', 'workshop.manage', 'quality_control.manage'],
            'automotive-technician' => $technician,
            'master-technician' => array_values(array_unique(array_merge($technician, ['inspections.approve', 'job_cards.assign']))),
            'diagnostic-technician' => $technician,
            'auto-electrician' => $technician,
            'body-repair-technician' => $technician,
            'painter' => $technician,
            'tyre-technician' => $technician,
            'automotive-quality-controller' => ['automotive.view', 'vehicles.view', 'job_cards.view', 'quality_control.manage', 'warranty.manage', 'automotive.reports'],
            'parts-manager' => ['automotive.view', 'automotive.dashboard', 'parts.view', 'parts.issue', 'parts.return', 'automotive.inventory', 'automotive.procurement', 'inventory.view', 'inventory.adjust', 'automotive.reports'],
            'automotive-store-keeper' => ['automotive.view', 'parts.view', 'parts.issue', 'parts.return', 'automotive.inventory', 'inventory.view', 'inventory.adjust'],
            'automotive-procurement-officer' => ['automotive.view', 'parts.view', 'automotive.procurement', 'automotive.inventory', 'finance.ap.view'],
            'automotive-sales-manager' => ['automotive.view', 'automotive.dashboard', 'clients.view', 'clients.create', 'clients.edit', 'vehicles.view', 'vehicle_sales.manage', 'automotive.finance', 'automotive.reports'],
            'automotive-salesperson' => ['automotive.view', 'clients.view', 'clients.create', 'vehicles.view', 'vehicle_sales.manage'],
            'fleet-manager' => ['automotive.view', 'automotive.dashboard', 'clients.view', 'vehicles.view', 'vehicles.create', 'vehicles.update', 'fleet.manage', 'bookings.manage', 'job_cards.view', 'automotive.reports'],
            'recovery-driver' => ['automotive.view', 'vehicles.view', 'bookings.manage', 'checkin.manage', 'job_cards.view'],
            'automotive-finance-manager' => ['automotive.view', 'automotive.dashboard', 'automotive.finance', 'automotive.reports', 'job_cards.view', 'finance.view', 'finance.ar.view', 'finance.ap.view', 'finance.gl.view', 'finance.reports.view'],
            'automotive-accountant' => ['automotive.view', 'automotive.finance', 'automotive.reports', 'finance.view', 'finance.ar.view', 'finance.gl.view', 'finance.reports.view'],
            'automotive-viewer' => ['automotive.view', 'automotive.dashboard', 'vehicles.view', 'job_cards.view', 'parts.view', 'automotive.reports'],
            'viewer' => ['automotive.view', 'automotive.dashboard', 'vehicles.view', 'job_cards.view', 'automotive.reports'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncSalonRolePermissions(): void
    {
        $all = [
            'salon.view', 'salon.manage', 'salon.reports',
            'salon.appointments.view', 'salon.appointments.manage',
            'salon.staff.view', 'salon.staff.manage',
            'salon.services.view', 'salon.services.manage',
            'salon.pos.view', 'salon.pos.manage',
            'salon.memberships.view', 'salon.memberships.manage',
            'salon.loyalty.view', 'salon.loyalty.manage',
            'salon.consultations.view', 'salon.consultations.manage',
            'salon.treatments.view', 'salon.treatments.manage',
            'salon.inventory.view', 'salon.inventory.manage',
            'salon.commissions.view', 'salon.commissions.manage',
            'salon.wellness.view', 'salon.wellness.manage',
        ];

        $map = [
            'salon-owner' => $all,
            'salon-manager' => array_diff($all, ['salon.commissions.manage']),
            'salon-receptionist' => ['salon.view', 'salon.appointments.view', 'salon.appointments.manage', 'salon.loyalty.view', 'salon.loyalty.manage', 'salon.pos.view', 'salon.pos.manage', 'salon.memberships.view'],
            'stylist' => ['salon.view', 'salon.appointments.view', 'salon.services.view', 'salon.consultations.view', 'salon.consultations.manage', 'salon.treatments.view', 'salon.treatments.manage', 'salon.inventory.view'],
            'spa-therapist' => ['salon.view', 'salon.appointments.view', 'salon.services.view', 'salon.consultations.view', 'salon.consultations.manage', 'salon.treatments.view', 'salon.treatments.manage', 'salon.wellness.view', 'salon.wellness.manage'],
            'salon-cashier' => ['salon.view', 'salon.pos.view', 'salon.pos.manage', 'salon.loyalty.view', 'salon.memberships.view'],
            'salon-inventory-clerk' => ['salon.view', 'salon.inventory.view', 'salon.inventory.manage', 'inventory.view', 'inventory.adjust'],
            'salon-branch-manager' => ['salon.view', 'salon.reports', 'salon.appointments.view', 'salon.staff.view', 'salon.services.view', 'salon.pos.view', 'salon.memberships.view', 'salon.loyalty.view', 'salon.inventory.view', 'salon.commissions.view'],
            'wellness-consultant' => ['salon.view', 'salon.consultations.view', 'salon.consultations.manage', 'salon.treatments.view', 'salon.wellness.view', 'salon.wellness.manage'],
            'salon-finance-officer' => ['salon.view', 'salon.reports', 'salon.pos.view', 'salon.commissions.view', 'finance.view', 'finance.gl.view'],
        ];

        foreach ($map as $slug => $permissions) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }

    private function syncCommunicationRolePermissions(): void
    {
        $employee = [
            'communication.view',
            'communication.send',
            'communication.create_group',
            'communication.upload',
            'communication.delete_own',
        ];

        $manager = array_values(array_unique(array_merge($employee, [
            'communication.create_channel',
            'communication.manage_channel',
            'communication.manage_group',
            'communication.announcements.create',
            'communication.announce',
            'communication.reports',
        ])));

        $administrator = array_values(array_unique(array_merge($manager, [
            'communication.manage',
            'communication.admin',
            'communication.moderate',
            'communication.announcements.manage',
            'communication.mass_mention',
            'communication.audit',
            'communication.settings',
        ])));

        $managerRoles = [
            'director', 'finance-manager', 'operations-manager', 'hr-manager', 'project-manager',
            'store-manager', 'hotel-manager', 'restaurant-manager', 'gym-owner', 'gym-manager',
            'retail-director', 'branch-manager', 'warehouse-manager', 'real-estate-director',
            'real-estate-branch-manager', 'property-manager', 'maintenance-manager',
            'agriculture-administrator', 'farm-manager', 'farm-supervisor', 'livestock-manager',
            'equipment-manager', 'salon-owner', 'salon-manager', 'salon-branch-manager',
            'printing-administrator', 'managing-director', 'printing-sales-manager',
            'production-manager', 'printing-store-manager', 'dispatch-officer',
        ];

        foreach (self::ROLES as $slug => $label) {
            $role = IamRole::where('business_id', ActiveBusiness::id())->where('slug', $slug)->first();

            if (! $role) {
                continue;
            }

            $permissions = $slug === 'viewer'
                ? ['communication.view']
                : (in_array($slug, ['system-administrator', 'business-administrator'], true)
                    ? $administrator
                    : (in_array($slug, $managerRoles, true) ? $manager : $employee));

            $role->permissions()->syncWithoutDetaching(IamPermission::whereIn('name', $permissions)->pluck('id'));
        }
    }

    private function browser($ua)
    {
        return str_contains($ua ?? '', 'Chrome') ? 'Chrome' : (str_contains($ua ?? '', 'Firefox') ? 'Firefox' : 'Other');
    }

    private function os($ua)
    {
        return str_contains($ua ?? '', 'Windows') ? 'Windows' : (str_contains($ua ?? '', 'Android') ? 'Android' : (str_contains($ua ?? '', 'iPhone') ? 'iOS' : 'Other'));
    }

    private function deviceName($ua)
    {
        return preg_match('/Mobile|Android|iPhone/i', $ua ?? '') ? 'Mobile' : 'Desktop';
    }
}
