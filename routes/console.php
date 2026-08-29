<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Fitness\Services\MembershipService;
use Modules\RealEstate\Services\TenantBillingAlertService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('fitness:expire-memberships {--remind : Send T-7/T-3/T-1/T-0 expiry reminders before expiring records}', function (MembershipService $memberships) {
    $reminders = $this->option('remind') ? $memberships->sendExpiryReminders() : 0;
    $expired = $memberships->expireDueMemberships();

    $this->info("Fitness memberships processed. Reminders: {$reminders}. Expired: {$expired}.");
})->purpose('Send Fitness & Gym membership expiry reminders and expire overdue memberships');

Artisan::command('real-estate:billing-alerts {--date= : Date to evaluate in YYYY-MM-DD format}', function (TenantBillingAlertService $alerts) {
    $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
    $sent = $alerts->sendDueAlerts($date);

    $this->info("Real Estate billing alerts processed for {$date->toDateString()}. Sent: {$sent}.");
})->purpose('Send due monthly and quarterly Real Estate tenant billing email alerts');

Schedule::command('fitness:expire-memberships --remind')->dailyAt('07:00');
Schedule::command('real-estate:billing-alerts')->dailyAt('07:30');
Schedule::command('subscriptions:billing-sweep')->dailyAt('08:00');
Schedule::command('payments:reconcile --gateway=mpesa --status=pending')->everyFifteenMinutes();
