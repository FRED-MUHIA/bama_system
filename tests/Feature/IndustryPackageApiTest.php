<?php

namespace Tests\Feature;

use Tests\TestCase;

class IndustryPackageApiTest extends TestCase
{
    public function test_industry_packages_can_be_listed(): void
    {
        $response = $this->getJson('/api/v1/industry-packages');

        $response
            ->assertOk()
            ->assertJsonPath('core_modules.crm', 'CRM')
            ->assertJsonPath('shared_features.0.name', 'Mobile Money')
            ->assertJsonPath('shared_features.0.description', 'Built-in support for M-Pesa and other local mobile payments alongside cash and cards.')
            ->assertJsonFragment(['slug' => 'retail'])
            ->assertJsonFragment(['slug' => 'printing_branding'])
            ->assertJsonFragment(['slug' => 'construction'])
            ->assertJsonFragment(['slug' => 'automotive'])
            ->assertJsonMissing(['slug' => 'banking']);

        foreach ($response->json('industries') as $industry) {
            $this->assertContains('Mobile Money', $industry['features']);
            $this->assertContains('Inventory Control', $industry['features']);
            $this->assertContains('Offline Mode', $industry['features']);
        }
    }

    public function test_industry_package_detail_exposes_operating_metadata(): void
    {
        $response = $this->getJson('/api/v1/industry-packages/printing_branding?sub_industry=enterprise');

        $response
            ->assertOk()
            ->assertJsonPath('slug', 'printing_branding')
            ->assertJsonPath('industry', 'Printing & Branding')
            ->assertJsonPath('supports.tenant_isolation', true)
            ->assertJsonPath('supports.role_permissions', true)
            ->assertJsonPath('supports.dynamic_menus', true)
            ->assertJsonPath('supports.dashboard_widgets', true)
            ->assertJsonPath('supports.api_endpoints', true)
            ->assertJsonPath('dashboard.features.0', 'Mobile Money')
            ->assertJsonPath('dashboard.features.1', 'Inventory Control')
            ->assertJsonPath('dashboard.features.2', 'Offline Mode')
            ->assertJsonFragment(['Electronic Job Tickets']);
    }

    public function test_industry_dashboard_features_can_be_retrieved(): void
    {
        $response = $this->getJson('/api/v1/industry-packages/retail/dashboard?sub_industry=book-store');

        $response
            ->assertOk()
            ->assertJsonPath('industry', 'Retail')
            ->assertJsonPath('sub_industry', 'Book Store')
            ->assertJsonFragment(['Point of Sale'])
            ->assertJsonStructure(['industry', 'sub_industry', 'summary', 'modules', 'features', 'dashboard_features']);
    }

    public function test_placeholder_industry_packages_are_hidden(): void
    {
        $this->getJson('/api/v1/industry-packages/construction?sub_industry=enterprise')
            ->assertOk()
            ->assertJsonPath('industry', 'Construction')
            ->assertJsonFragment(['BOQ Management']);

        $this->getJson('/api/v1/industry-packages/healthcare/dashboard?sub_industry=multi-branch')
            ->assertNotFound();
    }

    public function test_registration_dropdown_only_shows_implemented_industries(): void
    {
        $this->get('/register/company')
            ->assertOk()
            ->assertSeeText('Printing & Branding')
            ->assertSeeText('Automotive')
            ->assertSeeText('Retail')
            ->assertSeeText('Book Store')
            ->assertSeeText('Clothing Store')
            ->assertSeeText('Furniture Store')
            ->assertSeeText('Grocery Store')
            ->assertSeeText('Hardware Store')
            ->assertSeeText('Toy Store')
            ->assertSeeText('Construction')
            ->assertDontSeeText('Healthcare');
    }

    public function test_invalid_sub_industry_is_rejected(): void
    {
        $this->getJson('/api/v1/industry-packages/retail/dashboard?sub_industry=healthcare-enterprise')
            ->assertUnprocessable();
    }

    public function test_hospitality_package_exposes_domain_specific_metadata(): void
    {
        $response = $this->getJson('/api/v1/industry-packages/hospitality?sub_industry=hotel');

        $response
            ->assertOk()
            ->assertJsonPath('slug', 'hospitality')
            ->assertJsonPath('industry', 'Hospitality')
            ->assertJsonFragment(['name' => 'Hotel'])
            ->assertJsonFragment(['Occupancy Reports'])
            ->assertJsonFragment(['Hotel Manager'])
            ->assertJsonFragment(['Dashboard']);
    }
}
