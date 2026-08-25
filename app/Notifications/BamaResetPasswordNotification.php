<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class BamaResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(Lang::get('Reset your BAMA password'))
            ->theme('bama')
            ->greeting(Lang::get('Forgot your password? It happens to the best of us.'))
            ->line(Lang::get('To reset your password, click the button below. The link will expire in :count minutes.', [
                'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]))
            ->action(Lang::get('Reset your password'), $this->resetUrl($notifiable))
            ->line(Lang::get('If you do not want to change your password or did not request a reset, you can ignore and delete this email.'))
            ->salutation(Lang::get('BAMA secure workspace access'));
    }
}
