<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Railken\EloquentMapper\Concerns\Relationer;

class Book extends Model
{
    use SoftDeletes;
    use Relationer;

    protected $fillable = ['name'];

    public function author(): Relations\BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function tags(): Relations\MorphToMany
    {
        return $this->morphToMany(
            Tag::class,
            'source',
            'relations',
            'source_id',
            'target_id'
        )
        ->using(Relation::class)
        ->withPivotValue('target_type', Tag::class)
        ->withPivotValue('key', 'custom');
    }

    public function categories(): Relations\MorphMany
    {
        return $this->morphMany(Category::class, 'categorizable');
    }

    public function reviews(): Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Review::class,
            'book_reviews'
        );
    }

    public function worstReviews(): Relations\BelongsToMany
    {
        return $this->reviews()->where('reviews.rating', '<=', 2)->orWhere('reviews.content', 'like', '%bad%');
    }

    public function bestReviews(): Relations\BelongsToMany
    {
        return $this->reviews()->where(function ($q) {
            return $q
                ->orWhere('reviews.rating', '>=', 5)
                ->orWhere('reviews.content', 'like', '%good%')
            ;
        });
    }
}
