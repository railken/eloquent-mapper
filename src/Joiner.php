<?php

namespace Railken\EloquentMapper;

use Fico7489\Laravel\EloquentJoin\EloquentJoinBuilder;
use Illuminate\Database\Eloquent\Builder;

class Joiner
{
    public static function joinRelations(Builder $builder, string $relation)
    {
        $joinerBuilder = new EloquentJoinBuilder($builder->getQuery());

        return $joinerBuilder->setModel($builder->getModel())->joinRelations($relation);
    }
}
