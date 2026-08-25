<?php

namespace App\Notifications;

use App\Models\Business;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class WorkspaceVerifyEmailNotification extends VerifyEmail
{
    use Queueable;

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }

    public function toMail($notifiable): MailMessage
    {
        $profile = $this->profileNameFor($notifiable);
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify '.$notifiable->email.' for '.$profile)
            ->theme('bama')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You are receiving this because a BAMA workspace was created or updated for this account.')
            ->line('Workspace/profile: '.$profile)
            ->line('Account email: '.$notifiable->email)
            ->line('Click the button below to verify this email before opening the dashboard.')
            ->action('Verify email for '.$profile, $url)
            ->line('If you did not request this workspace, do not click the link. You can safely ignore this email.')
            ->salutation('BAMA secure workspace access');
    }

    private function profileNameFor($user): string
    {
        if ($business = ActiveBusiness::current()) {
            return $business->name;
        }

        if ($tenant = $user->currentTenant ?: ActiveTenant::current()) {
            if ($businessName = Business::withoutGlobalScopes()->where('tenant_id', $tenant->id)->orderBy('id')->value('name')) {
                return $businessName;
            }

            return $tenant->name;
        }

        if (Schema::hasTable('business_user') && Schema::hasTable('businesses')) {
            $businessName = DB::table('business_user')
                ->join('businesses', 'businesses.id', '=', 'business_user.business_id')
                ->where('business_user.user_id', $user->id)
                ->orderBy('businesses.id')
                ->value('businesses.name');

            if ($businessName) {
                return $businessName;
            }
        }

        return config('app.name', 'BAMA');
    }
}
