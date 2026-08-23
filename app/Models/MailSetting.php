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

        $smtp = config('mail.mailers.smtp', []);
        $port = (int) ($smtp['port'] ?? $this->port ?? 587);
        $scheme = match ($smtp['scheme'] ?? $this->scheme) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp' => 'smtp',
            default => $port === 465 ? 'smtps' : 'smtp',
        };
        $fromAddress = config('mail.from.address');
        if (blank($fromAddress) || $fromAddress === 'hello@example.com') {
            $fromAddress = $smtp['username'] ?? $this->from_address;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $smtp['host'] ?? $this->host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.username' => $smtp['username'] ?? $this->username,
            'mail.mailers.smtp.password' => $smtp['password'] ?? $this->password,
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $this->from_name,
        ]);
        app('mail.manager')->forgetMailers();
    }
}
