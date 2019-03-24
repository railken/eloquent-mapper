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

        $qb = (new Book())->newQuery();
        $joiner = new Joiner($qb);
        $joiner->joinRelations($array[0]);

        $this->assertEquals('select books.* from `books` left join `authors` as `author` on `author`.`id` = `books`.`author_id` group by `books`.`id`', $qb->toSql());
    }

    public function testValidationNestedRelationship()
    {
        $this->assertEquals(true, Mapper::isValidNestedRelation(Book::class, 'categories.children'));
        $this->assertEquals(true, Mapper::isValidNestedRelation(Book::class, 'categories.children.children.children'));
        $this->assertEquals(false, Mapper::isValidNestedRelation(Book::class, 'wrong'));
        $this->assertEquals(false, Mapper::isValidNestedRelation(Book::class, 'categories.children.children.childrens'));
    }
}
