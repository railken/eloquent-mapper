<?php

namespace Railken\EloquentMapper;

class Expression extends \Illuminate\Database\Query\Expression
{
    public static function __set_state(array $params)
    {
    	return new self($params['value']);
    }
}