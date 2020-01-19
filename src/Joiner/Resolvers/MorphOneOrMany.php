<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

class MorphOneOrMany extends HasOneOrMany
{
    public function applyWhere($join, $relation, $alias)
    {
        $join->where($this->getRelationName().'.'.$relation->getMorphType(), $relation->getMorphClass());

        parent::applyWhere($join, $relation, $alias);
    }
}
