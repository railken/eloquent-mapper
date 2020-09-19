<?php

namespace Railken\EloquentMapper\Tests;

use NilPortugues\Sql\QueryFormatter\Formatter;

abstract class BaseTest extends \Orchestra\Testbench\TestCase
{
    protected $formatter;

    /**
     * Setup the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('cache:clear');
        $this->artisan('mapper:generate');

        $this->formatter = new Formatter();

        app('eloquent.mapper')->boot();
    }

    public function assertQuery($x, $y)
    {
        $this->assertEquals(
            $this->formatter->format(strtolower(trim(preg_replace('!\s+!', ' ', $x)))),
            $this->formatter->format(strtolower(trim(preg_replace('!\s+!', ' ', $y))))
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            \Railken\EloquentMapper\EloquentMapperServiceProvider::class,
            \Railken\EloquentMapper\Tests\AppServiceProvider::class,
        ];
    }
}
