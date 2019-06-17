<?php

namespace Railken\EloquentMapper;

use Illuminate\Support\ServiceProvider;
use Railken\EloquentMapper\Console\Commands\Mapper;

class EloquentMapperServiceProvider extends ServiceProvider
{
    /**
     * @inherit
     */
    public function register()
    {
        $this->app->singleton('eloquent.mapper', function ($app) {
            return new \Railken\EloquentMapper\Helper();
        });
    }

    public function boot()
    {
        $this->commands([Mapper::class]);
    }
}
