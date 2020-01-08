<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Railken\EloquentMapper\Concerns\Relationer;

class Review extends Model
{
    use SoftDeletes;
    use Relationer;

    protected $fillable = ['rating', 'content'];
}
