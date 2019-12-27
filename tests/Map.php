<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Map as BaseMap;
use Railken\EloquentMapper\Tests\Models;

class Map extends BaseMap
{
	public function models(): array
	{
		return [
			Models\Author::class,
			Models\Book::class,
			Models\Category::class,
			Models\Relation::class,
			Models\Tag::class
		];
	}
}