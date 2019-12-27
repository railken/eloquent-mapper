<?php

namespace Railken\EloquentMapper\Tests;

use Illuminate\Support\ServiceProvider;
use Railken\EloquentMapper\Contracts;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * @inherit
	 */
	public function register()
	{
        $this->app->bind(Contracts\Map::class, Map::class);
	}
}