<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Joiner\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Tests\Models\Book;
use Railken\EloquentMapper\Tests\Models\Tag;
use Railken\EloquentMapper\Scopes\FilterScope;
use Railken\EloquentMapper\Contracts\Map as MapContract;

class HelperTest extends BaseTest
{
    public function testEventReloadRelationships()
    {
        $this->assertFalse(app('eloquent.mapper')->isValidNestedRelation(Book::class, 'bookshelf'));
        Book::belongs_to('bookshelf', Tag::class);
        event(new \Railken\EloquentMapper\Events\EloquentMapUpdate(Book::class));
        $this->assertTrue(app('eloquent.mapper')->isValidNestedRelation(Book::class, 'bookshelf'));
        Book::removeRelation('bookshelf');
    }

    public function testAttributeList()
    {
        $map = $this->app->make(MapContract::class);

        $this->assertEquals([
            'id',
            'name',
            'deleted_at',
            'created_at',
            'updated_at'
        ], $map->attributes(new Book));
    }

    public function testRelationsList()
    {
        $map = $this->app->make(MapContract::class);

        $this->assertEquals([
            'author',
            'tags',
            'categories',
            'reviews',
            'worstReviews',
            'bestReviews',
        ], array_keys($map->relations(new Book)));
    }

    public function testValidationRelation()
    {
        $this->assertTrue(app('eloquent.mapper')->isValidNestedRelation(Book::class, 'author'));
        $this->assertFalse(app('eloquent.mapper')->isValidNestedRelation(Book::class, 'bookshelf'));
        $this->assertTrue(app('eloquent.mapper')->isValidNestedRelation(Book::class, 'author.books.author.books.author'));
    }
}
