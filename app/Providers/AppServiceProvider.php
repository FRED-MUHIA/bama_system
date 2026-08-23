<?php

namespace App\Providers;

use App\Console\Commands\CreateSuperAdminCommand;
use App\Console\Commands\MailTestCommand;
use App\Console\Commands\PostgresWipeCommand;
use App\Console\Commands\PurgeNonSuperAdminUsersCommand;
use App\Models\Business;
use App\Services\NavigationManager;
use App\Services\ThemeManager;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use App\Support\SchemaCache;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Salon\Contracts\SalonSpaServiceContract;
use Modules\Salon\Services\SalonSpaService;
use Shared\Communication\Contracts\CommunicationServiceContract;
use Shared\Communication\Services\CommunicationService;
use Shared\Compliance\Etims\Contracts\EtimsComplianceServiceContract;
use Shared\Compliance\Etims\Services\EtimsComplianceService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EtimsComplianceServiceContract::class, EtimsComplianceService::class);
        $this->app->bind(CommunicationServiceContract::class, CommunicationService::class);
        $this->app->bind(SalonSpaServiceContract::class, SalonSpaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSuperAdminCommand::class,
                MailTestCommand::class,
                PostgresWipeCommand::class,
                PurgeNonSuperAdminUsersCommand::class,
            ]);
        }

        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $showAdminShell = $user && ! in_array($user->role, ['client_portal', 'super_admin'], true);
            $activeTenant = $user ? ActiveTenant::current() : null;
            $activeBusiness = $showAdminShell ? ActiveBusiness::current() : null;
            $themeManager = app(ThemeManager::class);
            $businessQuery = Business::where('is_active', true)->orderBy('name');
            $accessibleBusinessIds = $showAdminShell ? ActiveBusiness::accessibleBusinessIds() : null;
            $headerNotifications = collect();
            $headerUnreadCount = 0;
            $headerMessageCount = 0;

            if ($showAdminShell && SchemaCache::hasTable('notifications')) {
                $notificationQuery = DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->where('business_id', $activeBusiness?->id);

                $headerUnreadCount = (clone $notificationQuery)->where('status', 'Unread')->count();
                $headerMessageCount = (clone $notificationQuery)
                    ->where('status', 'Unread')
                    ->whereIn('notification_type', ['Message', 'Mention'])
                    ->count();
                $headerNotifications = (clone $notificationQuery)
                    ->latest('created_at')
                    ->limit(8)
                    ->get();
            }

            $view->with([
                'activeTenant' => $activeTenant,
                'activeBusiness' => $activeBusiness,
                'businesses' => $showAdminShell ? ($accessibleBusinessIds !== null ? $businessQuery->whereIn('id', $accessibleBusinessIds)->get() : $businessQuery->get()) : collect(),
                'platformMenu' => $showAdminShell ? app(NavigationManager::class)->sidebar() : collect(),
                'headerNotifications' => $headerNotifications,
                'headerUnreadCount' => $headerUnreadCount,
                'headerMessageCount' => $headerMessageCount,
                'tenantTheme' => $activeTenant ? $themeManager->current($activeTenant) : null,
                'tenantCssVariables' => $activeTenant ? $themeManager->cssVariables($activeTenant) : '--tenant-primary:#00A651; --tenant-secondary:#000000; --tenant-accent:#00A651;',
            ]);
        });
    }
}
