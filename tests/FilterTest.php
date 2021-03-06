<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Joiner\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Tests\Models\Book;
use Railken\EloquentMapper\Scopes\FilterScope;
use Railken\EloquentMapper\With\WithCollection;
use Railken\EloquentMapper\With\WithItem;
use Railken\EloquentMapper\Tests\Models\Tag;

class FilterTest extends BaseTest
{
    public function testValidFilteringBelongsToBasic()
    {
        $book = new Book();
        $qb = $book->newQuery();
        $scope = new FilterScope;
        $scope->apply($qb, 'author.name ct "hello"');

        $this->assertQuery('
            SELECT `books`.*
            FROM `books`
            LEFT JOIN `authors` AS `author`
                ON `books`.`author_id` = `author`.`id`
                    AND `author`.`deleted_at` is null
            WHERE `author`.`name` LIKE ?
                AND `books`.`deleted_at` is null
        ', $qb->toSql());
    }

    public function testValidFilteringBelongsToWith()
    {
        $book = new Book();
        $qb = $book->newQuery();

        $scope = new FilterScope;
        $scope->apply($qb, 'author.name ct "hello"', new WithCollection([
            new WithItem('tags', 'name ct "tagName"')
        ]));

        $this->assertQuery('
            SELECT `books`.*
            FROM `books`
            LEFT JOIN `authors` AS `author`
                ON `books`.`author_id` = `author`.`id`
                    AND `author`.`deleted_at` is null
            WHERE `author`.`name` LIKE ?
            	AND `books`.`deleted_at` is null
        ', $qb->toSql());

        $qbTag = (new Tag)->newQuery();
        $closure = $qb->getEagerLoads()['tags'];
        $closure($qbTag);

        $this->assertQuery('
            SELECT `tags`.* 
            FROM `tags` 
            WHERE `tags`.`name` LIKE ? 
                AND `tags`.`deleted_at` is null
        ', $qbTag->toSql());
    }
}
