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
                'mail.from.address' => $this->from_address,
                'mail.from.name' => $this->from_name,
            ]);
            app('mail.manager')->forgetMailers();

            return;
        }

        $fromAddress = $this->from_address;
        $providerDefaults = $this->providerDefaults($fromAddress);
        $host = $providerDefaults['host'] ?? $this->host;
        $port = (int) ($providerDefaults['port'] ?? $this->port ?? 587);
        $rawScheme = $providerDefaults['scheme'] ?? $this->scheme;
        $scheme = match ($rawScheme) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp' => 'smtp',
            default => $port === 465 ? 'smtps' : 'smtp',
        };

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.username' => $this->username,
            'mail.mailers.smtp.password' => $this->password,
            'mail.mailers.smtp.local_domain' => config('mail.mailers.smtp.local_domain'),
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $this->from_name,
        ]);
        app('mail.manager')->forgetMailers();
    }

    private function providerDefaults(string $fromAddress): array
    {
        $domain = str($fromAddress)->after('@')->lower()->toString();

        return match ($domain) {
            'gmail.com', 'googlemail.com' => ['host' => 'smtp.gmail.com', 'port' => 465, 'scheme' => 'ssl'],
            'yahoo.com', 'ymail.com', 'rocketmail.com' => ['host' => 'smtp.mail.yahoo.com', 'port' => 465, 'scheme' => 'ssl'],
            default => [],
        };
    }
}
