<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\IndustrySetupService;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndustryPackageController extends Controller
{
    public function index(IndustrySetupService $industries)
    {
        return response()->json([
            'core_modules' => config('industry-packages.core_modules', []),
            'shared_features' => config('industry-packages.shared_features', []),
            'industries' => $industries->implementedIndustries()
                ->map(fn (array $industry) => [
                    'name' => $industry['name'],
                    'slug' => $industry['slug'],
                    'description' => $industry['description'] ?? null,
                    'module_count' => count($industry['modules'] ?? []),
                    'modules' => $industry['modules'] ?? [],
                    'features' => $industry['features'] ?? [],
                    'sub_industries' => $industry['sub_industries'] ?? [],
                    'dashboard_features' => $industry['dashboard_features'] ?? [],
                    'links' => [
                        'package' => route('api.v1.industry-packages.show', ['industry' => $industry['slug']], false),
                        'dashboard' => route('api.v1.industry-packages.dashboard', ['industry' => $industry['slug']], false),
                    ],
                ])
                ->values(),
        ]);
    }

    public function show(string $industry, Request $request, IndustrySetupService $industries)
    {
        $data = $request->validate([
            'sub_industry' => ['nullable', 'string', 'max:80'],
        ]);

        abort_unless($industries->isImplemented($industry), 404);
        $this->validateSubIndustry($industries, $industry, $data['sub_industry'] ?? null);

        return response()->json($industries->package($industry, $data['sub_industry'] ?? null));
    }

    public function dashboard(string $industry, Request $request, IndustrySetupService $industries)
    {
        $data = $request->validate([
            'sub_industry' => ['nullable', 'string', 'max:80'],
        ]);

        abort_unless($industries->isImplemented($industry), 404);
        $this->validateSubIndustry($industries, $industry, $data['sub_industry'] ?? null);

        return response()->json($industries->dashboardFeatures($industry, $data['sub_industry'] ?? null));
    }

    public function tenant(Request $request, IndustrySetupService $industries)
    {
        $tenant = ActiveTenant::current();

        abort_unless($tenant, 404);

        $subIndustry = $tenant->sub_industry ?? $tenant->settings['sub_industry'] ?? $request->query('sub_industry');

        return response()->json([
            'tenant' => $tenant->only(['id', 'name', 'slug', 'industry', 'sub_industry', 'status']),
            'package' => $industries->package($tenant->industry ?: 'professional-services', $subIndustry),
        ]);
    }

    private function validateSubIndustry(IndustrySetupService $industries, string $industry, ?string $subIndustry): void
    {
        if (! $subIndustry) {
            return;
        }

        validator(
            ['sub_industry' => $subIndustry],
            ['sub_industry' => [Rule::in($industries->subIndustrySlugs($industry))]]
        )->validate();
    }
}
