<?php

namespace Railken\EloquentMapper;

use Doctrine\Common\Inflector\Inflector;
use Railken\EloquentMapper\Mapper;
use Illuminate\Support\Str;

trait Relationer
{
    use \Imanghafoori\Relativity\DynamicRelations;
    
    public static function getStaticMorphName()
    {
        return str_replace('_', '-', (new Inflector())->tableize((new \ReflectionClass(static::class))->getShortName()));
    }
}