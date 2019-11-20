<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Joiner\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Tests\Models\Book;

class BasicTest extends BaseTest
{
    public function testValidationNestedRelationship()
    {
        $this->assertEquals(true, Mapper::isValidNestedRelation(Book::class, 'categories.children'));
        $this->assertEquals(true, Mapper::isValidNestedRelation(Book::class, 'categories.children.children.children'));
        $this->assertEquals(false, Mapper::isValidNestedRelation(Book::class, 'wrong'));
        $this->assertEquals(false, Mapper::isValidNestedRelation(Book::class, 'categories.children.children.childrens'));
    }
}
