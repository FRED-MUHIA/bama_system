<?php

namespace Tests\Unit;

use App\Casts\TolerantEncryptedArray;
use App\Casts\TolerantEncryptedString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TolerantEncryptedCastsTest extends TestCase
{
    public function test_string_cast_encrypts_new_values_and_reads_plain_legacy_values(): void
    {
        $model = new SensitiveCastModel;
        $model->secret = 'classified notes';

        $raw = $model->getAttributes()['secret'];

        $this->assertNotSame('classified notes', $raw);
        $this->assertSame('classified notes', Crypt::decryptString($raw));
        $this->assertSame('classified notes', $model->secret);

        $legacy = new SensitiveCastModel;
        $legacy->setRawAttributes(['secret' => 'legacy plain text'], true);

        $this->assertSame('legacy plain text', $legacy->secret);
    }

    public function test_array_cast_encrypts_new_values_and_reads_plain_legacy_json(): void
    {
        $model = new SensitiveCastModel;
        $model->payload = ['api_key' => 'secret-key', 'mode' => 'live'];

        $raw = $model->getAttributes()['payload'];

        $this->assertNotSame(json_encode(['api_key' => 'secret-key', 'mode' => 'live']), $raw);
        $this->assertSame(['api_key' => 'secret-key', 'mode' => 'live'], json_decode(Crypt::decryptString($raw), true));
        $this->assertSame(['api_key' => 'secret-key', 'mode' => 'live'], $model->payload);

        $legacy = new SensitiveCastModel;
        $legacy->setRawAttributes(['payload' => '{"api_key":"legacy-key","mode":"test"}'], true);

        $this->assertSame(['api_key' => 'legacy-key', 'mode' => 'test'], $legacy->payload);
    }
}

class SensitiveCastModel extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'secret' => TolerantEncryptedString::class,
            'payload' => TolerantEncryptedArray::class,
        ];
    }
}
