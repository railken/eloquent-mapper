<?php

namespace Railken\EloquentMapper;

use Illuminate\Database\Eloquent\Builder;
use Railken\EloquentMapper\Builder as JoinBuilder;

class Joiner
{
    protected $builder;

    /**
     * @param \Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\Relation $builder
     * @param $model
     */
    public function __construct($builder, $model = null)
    {
        $this->builder = new JoinBuilder($builder->getQuery());
        $this->builder->setModel($model ? $model : $builder->getModel());
    }

    public function joinRelations(string $relation)
    {
        return $this->builder->joinRelations($relation);
    }
}
