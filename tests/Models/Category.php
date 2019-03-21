<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Category extends Model
{
    public function parent(): Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function children(): Relations\HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function categorizable(): Relations\MorphTo
    {
        return $this->morphTo();
    }
}
