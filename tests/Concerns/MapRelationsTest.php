<?php

namespace Railken\EloquentMapper\Tests\Concerns;

use Railken\EloquentMapper\Tests\Models\Book;
use Railken\EloquentMapper\Tests\Models\Author;
use Railken\EloquentMapper\Tests\BaseTest;

class MapRelationsTest extends BaseTest
{
	public function testSimple()
	{
		$array = (new Book)->mapRelations();

		print_r($array->toArray());

		$this->assertEquals(1, 1);
	}
}