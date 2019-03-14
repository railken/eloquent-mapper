<?php

namespace Railken\EloquentMapper\Tests\Concerns;

use Railken\EloquentMapper\Tests\Models\Book;
use Railken\EloquentMapper\Tests\Models\Author;
use Railken\EloquentMapper\Tests\BaseTest;

class MapRelationsTest extends BaseTest
{
	public function testSimple()
	{
		$array = (new Book)->mapKeysRelation();

		$this->assertEquals(1, 1);

		$qb = (new Book())->joinRelations($array[2]);

		$this->assertEquals($qb->toSql(), 'select books.* from `books` left join `authors` on `authors`.`id` = `books`.`author_id` left join `books` on `books`.`author_id` = `authors`.`id` left join `authors` on `authors`.`id` = `books`.`author_id` group by `books`.`id`');

	}
}