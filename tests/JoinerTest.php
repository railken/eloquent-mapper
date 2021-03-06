<?php

namespace Railken\EloquentMapper\Tests;

use Railken\EloquentMapper\Joiner\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Tests\Models\Book;
use Railken\EloquentMapper\Tests\Models\Author;
use Railken\EloquentMapper\Contracts\Joiner as JoinerContract;

class JoinerTest extends BaseTest
{
    public function testBelongsTo()
    {
        $qb = (new Book())->newQuery();

        app(JoinerContract::class)->leftJoin($qb, 'author');

        $this->assertQuery('
            SELECT *
            FROM `books`
            LEFT JOIN `authors` AS `author`
                ON `books`.`author_id` = `author`.`id`
                    AND `author`.`deleted_at` is null
            WHERE `books`.`deleted_at` is null
        ', $qb->toSql());
    }

    public function testBelongsToDouble()
    {
        $qb = (new Book())->newQuery();
        
        app(JoinerContract::class)->leftJoin($qb, 'author');
        app(JoinerContract::class)->leftJoin($qb, 'author');

        $this->assertQuery('
            SELECT *
            FROM `books`
            LEFT JOIN `authors` AS `author`
                ON `books`.`author_id` = `author`.`id`
                    AND `author`.`deleted_at` is null
            WHERE `books`.`deleted_at` is null
        ', $qb->toSql());
    }

    public function testHasMany()
    {
        $qb = (new Author())->newQuery();

        app(JoinerContract::class)->leftJoin($qb, 'books');

        $this->assertQuery('
            SELECT *
            FROM `authors`
            LEFT JOIN `books`
                ON `authors`.`id` = `books`.`author_id`
                    AND `books`.`deleted_at` is null
            WHERE `authors`.`deleted_at` is null
        ', $qb->toSql());
    }

    public function testMorphMany()
    {
        $qb = (new Book())->newQuery();

        app(JoinerContract::class)->leftJoin($qb, 'categories');

        $this->assertQuery('
            SELECT *
            FROM `books`
            LEFT JOIN `categories`
                ON `books`.`id` = `categories`.`categorizable_id`
                    AND `categories`.`categorizable_type` = ?
            WHERE `books`.`deleted_at` is null
        ', $qb->toSql());
    }

    public function testBelongsToAndHasMany()
    {
        $qb = (new Book())->newQuery();

        app(JoinerContract::class)->leftJoin($qb, 'author.books');

        $this->assertQuery('
            SELECT *
            FROM `books`
            LEFT JOIN `authors` AS `author`
                ON `books`.`author_id` = `author`.`id`
                    AND `author`.`deleted_at` is null
            LEFT JOIN `books` AS `author.books`
                ON `author`.`id` = `author.books`.`author_id`
                    AND `author.books`.`deleted_at` is null
            WHERE `books`.`deleted_at` is null
        ', $qb->toSql());
    }


    public function testMorphToMany()
    {
        $qb = (new Book())->newQuery();

        app(JoinerContract::class)->leftJoin($qb, 'tags');


        $this->assertQuery('
            SELECT *
            FROM `books`
            LEFT JOIN `relations` AS `tags_pivot`
                ON `books`.`id` = `tags_pivot`.`source_id` AND 
                   `tags_pivot`.`source_type` = ?
            LEFT JOIN `tags` AS `tags`
                ON `tags_pivot`.`target_id` = `tags`.`id` AND
                   `tags_pivot`.`target_type` = ? AND
                   `tags_pivot`.`key` = ? AND
                   `tags`.`deleted_at` is null
            WHERE `books`.`deleted_at` is null
        ', $qb->toSql());
    }

    public function testBelongsToMany()
    {
        $qb = (new Book())->newQuery();

        app(JoinerContract::class)->leftJoin($qb, 'reviews');

        $this->assertQuery('
            select * from `books` 
            left join `book_reviews` as `reviews_pivot` 
                on `books`.`id` = `reviews_pivot`.`book_id` 
            left join `reviews` as `reviews` 
                on `reviews_pivot`.`review_id` = `reviews`.`id` 
                and `reviews`.`deleted_at` is null 
            where `books`.`deleted_at` is null
        ', $qb->toSql());
    }

    public function testBelongsToManyWithOrWhere()
    {
        $qb = (new Book())->newQuery();

        app(JoinerContract::class)->leftJoin($qb, 'worstReviews');

        $this->assertQuery('
            select * from `books` 
            left join `book_reviews` as `worstreviews_pivot` 
                on `books`.`id` = `worstreviews_pivot`.`book_id` 
            left join `reviews` as `worstreviews` 
                on `worstreviews_pivot`.`review_id` = `worstreviews`.`id` 
                and (
                    `worstreviews`.`rating` <= ?
                    or `worstreviews`.`content` like ?
                )
                and `worstreviews`.`deleted_at` is null 
            where `books`.`deleted_at` is null
        ', $qb->toSql());
    }
}
