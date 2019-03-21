<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Book extends Model
{
    public function author(): Relations\BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function tags(): Relations\MorphMany
    {
        return $this->morphMany(Tag::class, 'taggable');
    }

    public function categories(): Relations\MorphMany
    {
        return $this->morphMany(Category::class, 'categorizable');
    }
}
