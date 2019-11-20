<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Book extends Model
{
    use SoftDeletes;

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
            'target_id',
            'source_id'
        )
        ->using(Relation::class)
        ->withPivotValue('target_type', Tag::class)
        ->withPivotValue('key', 'custom');
    }

    public function categories(): Relations\MorphMany
    {
        return $this->morphMany(Category::class, 'categorizable');
    }
}
