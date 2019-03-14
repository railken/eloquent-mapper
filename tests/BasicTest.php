<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Tests\BaseTest;
use Railken\EloquentMapper\Tests\Models\Book;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Joiner;

class BasicTest extends BaseTest
{
    public function testSimple()
    {
        $array = Mapper::mapKeysRelation(Book::class);

        $this->assertEquals(1, 1);

        $qb = (new Book())->newQuery();
        $joiner = new Joiner($qb);
        $joiner->joinRelations($array[2]);

        $this->assertEquals($qb->toSql(), 'select books.* from `books` left join `authors` on `authors`.`id` = `books`.`author_id` left join `books` on `books`.`author_id` = `authors`.`id` left join `authors` on `authors`.`id` = `books`.`author_id` group by `books`.`id`');
    }
}
