<?php

namespace Railken\EloquentMapper;

use Fico7489\Laravel\EloquentJoin\EloquentJoinBuilder;
use Illuminate\Database\Eloquent\Builder;

class Joiner
{	
	protected $builder;

	public function __construct(Builder $builder)
	{
        $this->builder = new EloquentJoinBuilder($builder->getQuery());
        $this->builder->setModel($builder->getModel());
	}

    public function joinRelations(string $relation)
    {
        return $this->builder->joinRelations($relation);
    }
}
