<?php

namespace Railken\EloquentMapper\Concerns;

use Ankurk91\Eloquent\MorphToOne;
use Imanghafoori\Relativity\AbstractRelation;

trait HasMorphToOne
{
    use MorphToOne;

    /**
     * Define a polymorphic many-to-one relationship.
     *
     * @param string $relationName
     */
    public static function morph_to_one($relationName)
    {
        return new AbstractRelation([
            'morphToOne', 
            static::class, 
            $relationName, 
            array_slice(func_get_args(), 1)
        ]);
    }
}