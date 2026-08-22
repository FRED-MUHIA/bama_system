<?php

namespace Tests;

use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use App\Support\SchemaCache;
use App\Services\ModuleRegistry;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetActiveContext();
    }

    protected function tearDown(): void
    {
        $this->resetActiveContext();
        parent::tearDown();
    }

    protected function resetActiveContext(): void
    {
        ModuleRegistry::flushCache();
        SchemaCache::flush();

        foreach ([ActiveTenant::class => ['current', 'fallback', 'id', 'idResolved', 'tableExists', 'columnExists'], ActiveBusiness::class => ['current', 'default']] as $class => $properties) {
            $reflection = new \ReflectionClass($class);
            foreach ($properties as $property) {
                $ref = $reflection->getProperty($property);
                $ref->setAccessible(true);
                $ref->setValue(null, match ($property) {
                    'idResolved' => false,
                    'tableExists', 'columnExists' => [],
                    default => null,
                });
            }
        }
    }
}
