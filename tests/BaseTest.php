<?php

namespace Railken\EloquentMapper\Tests;

abstract class BaseTest extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('cache:clear');
    }

    protected function getPackageProviders($app)
    {
        return [
            \Railken\EloquentMapper\EloquentMapperServiceProvider::class,
        ];
    }
}
