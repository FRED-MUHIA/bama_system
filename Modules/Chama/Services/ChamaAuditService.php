<?php

namespace Modules\Chama\Services;

use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use App\Support\SchemaCache;
use Illuminate\Database\Eloquent\Model;
use Modules\Chama\Models\ChamaAuditEvent;

class ChamaAuditService
{
    public function record(string $event, ?Model $model = null, array $old = [], array $new = [], ?string $reference = null): void
    {
        if (! SchemaCache::hasTable('chama_audit_events') || ! ActiveTenant::id()) {
            return;
        }

        ChamaAuditEvent::create([
            'tenant_id' => ActiveTenant::id(),
            'business_id' => ActiveBusiness::id(),
            'actor_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: ($model?->attributesToArray() ?: null),
            'reference' => $reference,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
