<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants') && ! Schema::hasColumn('tenants', 'sub_industry')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('sub_industry')->nullable()->after('industry')->index();
            });
        }

        $this->createRoomTables();
        $this->createGuestTables();
        $this->createOperationsTables();
        $this->createRestaurantAndEventTables();
        $this->registerHospitality();
    }

    public function down(): void
    {
        foreach ([
            'hospitality_loyalty_members',
            'hospitality_event_bookings',
            'hospitality_restaurant_orders',
            'hospitality_maintenance_requests',
            'hospitality_housekeeping_tasks',
            'hospitality_check_outs',
            'hospitality_check_ins',
            'hospitality_reservation_guests',
            'hospitality_reservations',
            'hospitality_guest_profiles',
            'hospitality_rooms',
            'hospitality_room_types',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'sub_industry')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropIndex(['sub_industry']);
                $table->dropColumn('sub_industry');
            });
        }
    }

    private function createRoomTables(): void
    {
        if (! Schema::hasTable('hospitality_room_types')) {
            Schema::create('hospitality_room_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('capacity')->default(1);
                $table->decimal('base_price', 14, 2)->default(0);
                $table->json('amenities')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['business_id', 'slug'], 'hosp_room_types_biz_slug_unique');
            });
        }

        if (! Schema::hasTable('hospitality_rooms')) {
            Schema::create('hospitality_rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('room_type_id')->nullable()->constrained('hospitality_room_types')->nullOnDelete();
                $table->string('room_number');
                $table->string('status')->default('Available')->index();
                $table->unsignedSmallInteger('capacity')->default(1);
                $table->string('floor')->nullable();
                $table->string('view')->nullable();
                $table->string('bed_type')->nullable();
                $table->json('amenities')->nullable();
                $table->decimal('price_per_night', 14, 2)->default(0);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'room_number'], 'hosp_rooms_biz_number_unique');
            });
        }
    }

    private function createGuestTables(): void
    {
        if (! Schema::hasTable('hospitality_guest_profiles')) {
            Schema::create('hospitality_guest_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
                $table->string('full_name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('nationality')->nullable();
                $table->string('passport_number')->nullable();
                $table->string('id_number')->nullable();
                $table->text('address')->nullable();
                $table->json('preferences')->nullable();
                $table->boolean('vip_status')->default(false);
                $table->boolean('blacklist_flag')->default(false);
                $table->string('loyalty_level')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'email'], 'hosp_guests_biz_email_idx');
                $table->index(['business_id', 'phone'], 'hosp_guests_biz_phone_idx');
            });
        }
    }

    private function createOperationsTables(): void
    {
        if (! Schema::hasTable('hospitality_reservations')) {
            Schema::create('hospitality_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('guest_profile_id')->nullable()->constrained('hospitality_guest_profiles')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('room_id')->nullable()->constrained('hospitality_rooms')->nullOnDelete();
                $table->foreignId('room_type_id')->nullable()->constrained('hospitality_room_types')->nullOnDelete();
                $table->string('reservation_number')->unique();
                $table->date('arrival_date');
                $table->date('departure_date');
                $table->unsignedSmallInteger('adults')->default(1);
                $table->unsignedSmallInteger('children')->default(0);
                $table->text('special_requests')->nullable();
                $table->string('booking_source')->default('Website');
                $table->string('status')->default('Pending')->index();
                $table->decimal('deposit_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamp('checked_out_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'arrival_date', 'departure_date'], 'hosp_res_biz_dates_idx');
            });
        }

        if (! Schema::hasTable('hospitality_reservation_guests')) {
            Schema::create('hospitality_reservation_guests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('reservation_id')->constrained('hospitality_reservations')->cascadeOnDelete();
                $table->foreignId('guest_profile_id')->constrained('hospitality_guest_profiles')->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->unique(['reservation_id', 'guest_profile_id'], 'hosp_res_guests_unique');
            });
        }

        if (! Schema::hasTable('hospitality_check_ins')) {
            Schema::create('hospitality_check_ins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('reservation_id')->constrained('hospitality_reservations')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('hospitality_rooms')->restrictOnDelete();
                $table->foreignId('guest_profile_id')->nullable()->constrained('hospitality_guest_profiles')->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->string('access_code')->nullable();
                $table->decimal('deposit_amount', 14, 2)->default(0);
                $table->timestamp('checked_in_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitality_check_outs')) {
            Schema::create('hospitality_check_outs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('reservation_id')->constrained('hospitality_reservations')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('hospitality_rooms')->restrictOnDelete();
                $table->foreignId('guest_profile_id')->nullable()->constrained('hospitality_guest_profiles')->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('receipt_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('restaurant_charges', 14, 2)->default(0);
                $table->decimal('event_charges', 14, 2)->default(0);
                $table->decimal('other_charges', 14, 2)->default(0);
                $table->decimal('final_amount', 14, 2)->default(0);
                $table->timestamp('checked_out_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitality_housekeeping_tasks')) {
            Schema::create('hospitality_housekeeping_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('room_id')->nullable()->constrained('hospitality_rooms')->nullOnDelete();
                $table->string('task_type')->default('Room Cleaning');
                $table->string('status')->default('Pending')->index();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('completion_minutes')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitality_maintenance_requests')) {
            Schema::create('hospitality_maintenance_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('room_id')->nullable()->constrained('hospitality_rooms')->nullOnDelete();
                $table->string('category')->default('General');
                $table->string('priority')->default('Medium')->index();
                $table->string('status')->default('Open')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createRestaurantAndEventTables(): void
    {
        if (! Schema::hasTable('hospitality_restaurant_orders')) {
            Schema::create('hospitality_restaurant_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('reservation_id')->nullable()->constrained('hospitality_reservations')->nullOnDelete();
                $table->foreignId('guest_profile_id')->nullable()->constrained('hospitality_guest_profiles')->nullOnDelete();
                $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
                $table->string('table_number')->nullable();
                $table->foreignId('waiter_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('kitchen_status')->default('Queued');
                $table->string('billing_status')->default('Open');
                $table->decimal('total', 14, 2)->default(0);
                $table->timestamp('served_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitality_event_bookings')) {
            Schema::create('hospitality_event_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('guest_profile_id')->nullable()->constrained('hospitality_guest_profiles')->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->string('booking_number')->unique();
                $table->string('venue_name');
                $table->string('event_type')->nullable();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->unsignedInteger('attendees')->default(0);
                $table->string('package_name')->nullable();
                $table->json('catering')->nullable();
                $table->json('equipment')->nullable();
                $table->string('status')->default('Pending')->index();
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitality_loyalty_members')) {
            Schema::create('hospitality_loyalty_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('guest_profile_id')->constrained('hospitality_guest_profiles')->cascadeOnDelete();
                $table->string('membership_number')->unique();
                $table->string('level')->default('Bronze')->index();
                $table->unsignedInteger('points_balance')->default(0);
                $table->unsignedInteger('lifetime_points')->default(0);
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('last_redemption_at')->nullable();
                $table->json('rewards')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'guest_profile_id'], 'hosp_loyalty_biz_guest_unique');
            });
        }
    }

    private function registerHospitality(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $now = now();
        $menus = [
            ['hospitality-dashboard', 'Dashboard', 'hospitality.dashboard', 'bi-speedometer2', ['hospitality.view']],
            ['hospitality-reservations', 'Reservations', 'hospitality.reservations.index', 'bi-calendar-check', ['hospitality.reservations.view', 'hospitality.reservations.manage']],
            ['hospitality-rooms', 'Rooms', 'hospitality.rooms.index', 'bi-door-open', ['hospitality.rooms.view', 'hospitality.rooms.manage']],
            ['hospitality-guests', 'Guests', 'hospitality.guests.index', 'bi-person-heart', ['hospitality.guests.view', 'hospitality.guests.manage']],
            ['hospitality-check-in', 'Check-In', 'hospitality.check-ins.index', 'bi-box-arrow-in-right', ['hospitality.checkins.manage']],
            ['hospitality-check-out', 'Check-Out', 'hospitality.check-outs.index', 'bi-box-arrow-right', ['hospitality.checkouts.manage']],
            ['hospitality-housekeeping', 'Housekeeping', 'hospitality.housekeeping.index', 'bi-brush', ['hospitality.housekeeping.view', 'hospitality.housekeeping.manage']],
            ['hospitality-maintenance', 'Maintenance', 'hospitality.maintenance.index', 'bi-tools', ['hospitality.maintenance.view', 'hospitality.maintenance.manage']],
            ['hospitality-restaurant', 'Restaurant', 'hospitality.restaurant.index', 'bi-cup-hot', ['hospitality.restaurant.view', 'hospitality.restaurant.manage']],
            ['hospitality-events', 'Events', 'hospitality.events.index', 'bi-calendar-event', ['hospitality.events.view', 'hospitality.events.manage']],
            ['hospitality-loyalty', 'Loyalty Program', 'hospitality.guests.index', 'bi-gem', ['hospitality.loyalty.view', 'hospitality.loyalty.manage']],
            ['hospitality-reports', 'Reports', 'hospitality.reports.index', 'bi-bar-chart', ['hospitality.reports']],
        ];

        DB::table('modules')->updateOrInsert(
            ['slug' => 'hospitality'],
            [
                'name' => 'Hospitality',
                'namespace' => 'Modules\\Hospitality',
                'type' => 'industry',
                'industry' => 'hospitality',
                'icon' => 'bi-cup-hot',
                'route' => 'hospitality.dashboard',
                'permissions' => json_encode(['hospitality.view', 'hospitality.manage', 'hospitality.reports']),
                'menu' => json_encode(['label' => 'Hospitality', 'group' => 'Industry', 'icon' => 'bi-cup-hot', 'route' => 'hospitality.dashboard', 'children' => $menus]),
                'widgets' => json_encode(['hospitality-occupancy-rate', 'hospitality-available-rooms', 'hospitality-revenue-today']),
                'is_core' => false,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $hospitalityId = DB::table('modules')->where('slug', 'hospitality')->value('id');
        if (Schema::hasTable('industry_modules') && $hospitalityId) {
            DB::table('industry_modules')->updateOrInsert(
                ['industry' => 'hospitality', 'module_id' => $hospitalityId],
                ['enabled_by_default' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach ($menus as [$slug, $label, $route, $icon, $permissions]) {
            DB::table('modules')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $label,
                    'namespace' => 'Modules\\Hospitality',
                    'type' => 'industry',
                    'industry' => 'hospitality',
                    'icon' => $icon,
                    'route' => $route,
                    'permissions' => json_encode($permissions),
                    'menu' => json_encode(['label' => $label, 'group' => 'Hospitality', 'icon' => $icon, 'route' => $route]),
                    'widgets' => json_encode([$slug.'-summary']),
                    'is_core' => false,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $moduleId = DB::table('modules')->where('slug', $slug)->value('id');
            if (Schema::hasTable('industry_modules') && $moduleId) {
                DB::table('industry_modules')->updateOrInsert(
                    ['industry' => 'hospitality', 'module_id' => $moduleId],
                    ['enabled_by_default' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        $widgets = [
            'occupancy-rate' => 'Occupancy Rate',
            'available-rooms' => 'Available Rooms',
            'todays-check-ins' => "Today's Check-ins",
            'todays-check-outs' => "Today's Check-outs",
            'revenue-today' => 'Revenue Today',
            'monthly-revenue' => 'Monthly Revenue',
            'pending-reservations' => 'Pending Reservations',
            'guest-satisfaction' => 'Guest Satisfaction',
            'maintenance-requests' => 'Maintenance Requests',
            'restaurant-sales' => 'Restaurant Sales',
        ];

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ($widgets as $slug => $name) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => 'hospitality-'.$slug],
                    [
                        'name' => $name,
                        'module_slug' => 'hospitality',
                        'industry' => 'hospitality',
                        'component' => 'hospitality.widgets.metric-card',
                        'permission' => 'hospitality.view',
                        'settings_schema' => json_encode(['supports_tenant_filters' => true, 'supports_period_filters' => true]),
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        if (Schema::hasTable('iam_permissions')) {
            $permissions = collect($menus)->flatMap(fn ($menu) => $menu[4])
                ->merge(['hospitality.view', 'hospitality.manage', 'hospitality.billing.manage', 'hospitality.finance.post'])
                ->unique();

            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    ['module' => 'hospitality', 'description' => Str::headline(str_replace('.', ' ', $permission)), 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('iam_roles')) {
            $roles = [
                'hotel-manager' => ['Hotel Manager', ['hospitality.view', 'hospitality.manage', 'hospitality.reports']],
                'front-desk-officer' => ['Front Desk Officer', ['hospitality.view', 'hospitality.reservations.manage', 'hospitality.checkins.manage', 'hospitality.checkouts.manage']],
                'reservations-officer' => ['Reservations Officer', ['hospitality.view', 'hospitality.reservations.manage']],
                'housekeeping-supervisor' => ['Housekeeping Supervisor', ['hospitality.view', 'hospitality.housekeeping.manage']],
                'housekeeping-staff' => ['Housekeeping Staff', ['hospitality.housekeeping.view']],
                'maintenance-officer' => ['Maintenance Officer', ['hospitality.maintenance.manage']],
                'restaurant-manager' => ['Restaurant Manager', ['hospitality.restaurant.manage']],
                'restaurant-staff' => ['Restaurant Staff', ['hospitality.restaurant.view']],
                'events-coordinator' => ['Events Coordinator', ['hospitality.events.manage']],
                'finance-officer' => ['Finance Officer', ['hospitality.billing.manage', 'hospitality.finance.post']],
            ];

            $businessIds = Schema::hasTable('businesses') ? DB::table('businesses')->pluck('id') : collect([null]);
            foreach ($businessIds as $businessId) {
                foreach ($roles as $slug => [$name, $rolePermissions]) {
                    DB::table('iam_roles')->updateOrInsert(
                        ['business_id' => $businessId, 'slug' => $slug],
                        ['name' => $name, 'description' => 'Hospitality role', 'is_system' => true, 'updated_at' => $now, 'created_at' => $now]
                    );

                    if (Schema::hasTable('iam_permission_role')) {
                        $roleId = DB::table('iam_roles')->where('business_id', $businessId)->where('slug', $slug)->value('id');
                        $permissionIds = DB::table('iam_permissions')->whereIn('name', $rolePermissions)->pluck('id');

                        foreach ($permissionIds as $permissionId) {
                            DB::table('iam_permission_role')->updateOrInsert([
                                'iam_role_id' => $roleId,
                                'iam_permission_id' => $permissionId,
                            ]);
                        }
                    }
                }
            }
        }
    }
};
