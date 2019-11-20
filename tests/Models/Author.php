<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Author extends Model
{
	use SoftDeletes;
	
    public function books(): Relations\HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function tags(): Relations\MorphMany
    {
        return $this->morphMany(Tag::class, 'taggable');
    }
}
