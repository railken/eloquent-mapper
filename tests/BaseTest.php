<?php

namespace Railken\EloquentMapper\Tests;

abstract class BaseTest extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->artisan('cache:clear');
    }

    protected function getPackageProviders($app)
    {
        return [
        	\BeyondCode\ErdGenerator\ErdGeneratorServiceProvider::class
        ];
    }
}
