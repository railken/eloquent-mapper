<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Tests\Models\Book;

class BasicTest extends BaseTest
{
    public function testSimple()
    {
        $array = Mapper::mapKeysRelation(Book::class);

        $this->assertEquals(1, 1);

        $qb = (new Book())->newQuery();
        $joiner = new Joiner($qb);
        $joiner->joinRelations($array[2]);

        print_r($array[2]);

        $this->assertEquals('select books.* from `books` left join `authors` as `author` on `author`.`id` = `books`.`author_id` left join `books` as `author.books` on `author.books`.`author_id` = `author`.`id` left join `authors` as `author.books.author` on `author.books.author`.`id` = `author.books`.`author_id` group by `books`.`id`', $qb->toSql());
    }
}
