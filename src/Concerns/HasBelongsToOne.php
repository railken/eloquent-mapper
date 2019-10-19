<?php

namespace Railken\EloquentMapper\Concerns;

use Ankurk91\Eloquent\BelongsToOne;
use Imanghafoori\Relativity\AbstractRelation;

trait HasBelongsToOne
{
	use BelongsToOne;

    /**
     * Define a polymorphic belongs-to-one relationship.
     *
     * @param string $relationName
     */
    public static function belongs_to_one($relationName)
    {
        return new AbstractRelation([
            'belongsToOne', 
            static::class, 
            $relationName, 
            array_slice(func_get_args(), 1)
        ]);
    }
}