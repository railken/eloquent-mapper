<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Joiner\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Tests\Models\Book;

class JoinerTest extends BaseTest
{
    public function testBelongsTo()
    {
        $qb = (new Book())->newQuery();

        app(\Railken\EloquentMapper\Contracts\Joiner::class)->leftJoin($qb, 'author');

        $this->assertEquals('select * from `books` left join `authors` as `author` on `books`.`author_id` = `author`.`id` and `author`.`deleted_at` is null', $qb->toSql());


    }
}
