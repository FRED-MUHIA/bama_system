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
        $usesOwnSmtp = filled($this->username) && filled($this->password);
        $port = (int) ($usesOwnSmtp ? $this->port : ($smtp['port'] ?? $this->port ?? 587));
        $scheme = match ($usesOwnSmtp ? $this->scheme : ($smtp['scheme'] ?? $this->scheme)) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp' => 'smtp',
            default => $port === 465 ? 'smtps' : 'smtp',
        };
        $fromAddress = $this->from_address;

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $usesOwnSmtp ? $this->host : ($smtp['host'] ?? $this->host),
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.username' => $usesOwnSmtp ? $this->username : ($smtp['username'] ?? $this->username),
            'mail.mailers.smtp.password' => $usesOwnSmtp ? $this->password : ($smtp['password'] ?? $this->password),
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $this->from_name,
        ]);
        app('mail.manager')->forgetMailers();
    }
}
