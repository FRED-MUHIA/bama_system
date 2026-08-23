<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'enabled', 'host', 'port', 'scheme', 'username', 'password', 'from_address', 'from_name'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'username' => 'encrypted', 'password' => 'encrypted'];
    }

    public function apply(): void
    {
        if (! $this->enabled) {
            return;
        }

        $usesOwnSmtp = filled($this->username) && filled($this->password);

        if (! $usesOwnSmtp) {
            config([
                'mail.default' => 'sendmail',
                'mail.from.address' => $this->from_address,
                'mail.from.name' => $this->from_name,
            ]);
            app('mail.manager')->forgetMailers();

            return;
        }

        $port = (int) ($this->port ?? 587);
        $scheme = match ($this->scheme) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp' => 'smtp',
            default => $port === 465 ? 'smtps' : 'smtp',
        };
        $fromAddress = $this->from_address;
        $fromDomain = str($fromAddress)->after('@')->lower()->toString();

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.username' => $this->username,
            'mail.mailers.smtp.password' => $this->password,
            'mail.mailers.smtp.local_domain' => $fromDomain ?: config('mail.mailers.smtp.local_domain'),
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $this->from_name,
        ]);
        app('mail.manager')->forgetMailers();
    }
}
