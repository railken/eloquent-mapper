<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public function taggable()
    {
        return $this->morphTo();
    }
}
