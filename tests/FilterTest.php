<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Joiner\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Tests\Models\Book;
use Railken\EloquentMapper\Scopes\FilterScope;

class BasicTest extends BaseTest
{
    public function testValidFilteringBelongsTo()
    {
    	$book = new Book();
    	$qb = $book->newQuery();

    	$scope = new FilterScope;
    	$scope->apply($qb, 'author.name ct "hello"');

        $this->assertQuery('
            SELECT *
            FROM `books`
            LEFT JOIN `authors` AS `author`
                ON `books`.`author_id` = `author`.`id`
                    AND `author`.`deleted_at` is null
            WHERE `author`.`name` LIKE ?
            	AND `books`.`deleted_at` is null
        ', $qb->toSql());
    }
}
