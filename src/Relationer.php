<?php

namespace Railken\EloquentMapper;

use Doctrine\Common\Inflector\Inflector;
use Imanghafoori\Relativity\DynamicRelations;
use Railken\EloquentMapper\Concerns\HasBelongsToOne;
use Railken\EloquentMapper\Concerns\HasMorphToOne;

trait Relationer
{
    use DynamicRelations;
    use HasBelongsToOne;
    use HasMorphToOne;

    public static function getStaticMorphName()
    {
        return str_replace('_', '-', (new Inflector())->tableize((new \ReflectionClass(static::class))->getShortName()));
    }
}
