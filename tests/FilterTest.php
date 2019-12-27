<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Joiner\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Tests\Models\Book;
use Railken\EloquentMapper\Scopes\FilterScope;
use Railken\EloquentMapper\Collections\With\WithCollection;
use Railken\EloquentMapper\Collections\With\WithItem;
use Railken\EloquentMapper\Tests\Models\Tag;

class BasicTest extends BaseTest
{
    public function testValidFilteringBelongsTo()
    {
    	$book = new Book();
    	$qb = $book->newQuery();

    	$scope = new FilterScope;
    	$scope->apply($qb, 'author.name ct "hello"', new WithCollection([
            new WithItem('tags', 'name ct "tagName"')
        ]));

        $this->assertQuery('
            SELECT *
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
