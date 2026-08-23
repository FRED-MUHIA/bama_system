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
        $scheme = match ($this->scheme) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp' => 'smtp',
            default => ((int) $this->port === 465 ? 'smtps' : 'smtp'),
        };

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $this->port,
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.username' => $this->username ?: ($smtp['username'] ?? null),
            'mail.mailers.smtp.password' => ($smtp['password'] ?? null) ?: $this->password,
            'mail.from.address' => $this->from_address,
            'mail.from.name' => $this->from_name,
        ]);
        app('mail.manager')->forgetMailers();
    }
}
