<?php

namespace Modules\Retail\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Retail\Models\RetailEcommerceIntegration;
use Modules\Retail\Services\RetailEcommerceCatalogService;

class RetailEcommerceCatalogController extends Controller
{
    public function products(Request $request, int $integrationId, RetailEcommerceCatalogService $catalog)
    {
        $integration = $this->integration($integrationId);
        $this->authorizeIntegration($request, $integration);
        $integration->update(['last_product_sync_at' => now()]);

        return response()->json([
            'data' => $catalog->products($integration, $request->only(['q', 'category_id', 'updated_since', 'limit'])),
            'meta' => $this->meta($integration),
        ]);
    }

    public function categories(Request $request, int $integrationId, RetailEcommerceCatalogService $catalog)
    {
        $integration = $this->integration($integrationId);
        $this->authorizeIntegration($request, $integration);

        return response()->json([
            'data' => $catalog->categories($integration),
            'meta' => $this->meta($integration),
        ]);
    }

    public function pricing(Request $request, int $integrationId, RetailEcommerceCatalogService $catalog)
    {
        $integration = $this->integration($integrationId);
        $this->authorizeIntegration($request, $integration);

        return response()->json([
            'data' => $catalog->pricing($integration),
            'meta' => $this->meta($integration),
        ]);
    }

    private function authorizeIntegration(Request $request, RetailEcommerceIntegration $integration): void
    {
        abort_unless($integration->status === 'Active', 403, 'Ecommerce integration is not active.');

        $provided = $request->bearerToken() ?: $request->query('api_key') ?: $request->header('X-Retail-Api-Key');
        $expected = data_get($integration->settings, 'api_key');

        abort_unless($expected && $provided && hash_equals($expected, $provided), 401, 'Invalid ecommerce integration key.');
    }

    private function integration(int $integrationId): RetailEcommerceIntegration
    {
        return RetailEcommerceIntegration::withoutGlobalScopes()->findOrFail($integrationId);
    }

    private function meta(RetailEcommerceIntegration $integration): array
    {
        return [
            'integration_id' => $integration->id,
            'channel' => $integration->channel,
            'external_store_id' => $integration->external_store_id,
            'website_url' => data_get($integration->settings, 'website_url'),
            'synced_at' => now()->toISOString(),
        ];
    }
}
