<?php

namespace Railken\EloquentMapper;

use Illuminate\Support\ServiceProvider;
use Railken\EloquentMapper\Console\Commands\Mapper;
use Illuminate\Support\Facades\Event;

class EloquentMapperServiceProvider extends ServiceProvider
{
    /**
     * @inherit
     */
    public function register()
    {
        $this->app->singleton('eloquent.mapper', Helper::class);
        $this->app->bind(Contracts\Joiner::class, Joiner\Joiner::class);
        $this->app->bind(Contracts\Map::class, Map::class);
    }

    public function boot()
    {
        $this->commands([Mapper::class]);

        Event::listen(Events\EloquentMapUpdate::class, function () {
            $this->app->get('eloquent.mapper')->boot();
        });
    }
}
