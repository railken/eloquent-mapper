<?php

namespace Railken\EloquentMapper;

use Illuminate\Database\Eloquent\Builder;
use Railken\EloquentMapper\Builder as JoinBuilder;

class Joiner
{
    protected $builder;

    /**
     * @param \Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\Relation $builder
     */
    public function __construct($builder)
    {
        $this->builder = new JoinBuilder($builder->getQuery());
        $this->builder->setModel($builder->getModel());
    }

    public function joinRelations(string $relation)
    {
        return $this->builder->joinRelations($relation);
    }
}
