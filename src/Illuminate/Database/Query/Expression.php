<?php

namespace Railken\EloquentMapper\Illuminate\Database\Query;

use Illuminate\Database\Query\Expression as BaseExpression;

// Fix serialization on cache file
class Expression extends BaseExpression
{
    public static function __set_state(array $params)
    {
    	return new self($params['value']);
    }
}