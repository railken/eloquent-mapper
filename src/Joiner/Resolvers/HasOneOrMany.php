<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

class HasOneOrMany extends Base
{	
	public function resolve(Builder $builder)
	{	
		$method = $this->getMethod();

		if (!$this->isAlreadyJoined($builder, $this->getJoinQuery())) {

	        $builder->$method($this->getJoinQuery(), function ($join) {

				$relation = $this->getRelation();

		        $targetKey = $this->getKeyFromRelation($relation, 'foreignKey');
		        $sourceKey = $this->getKeyFromRelation($relation, 'parentKey');

	            $join->on(
	                $this->parseAliasableKey($this->getSourceTable(), $sourceKey),
	                '=', 
	                $this->parseAliasableKey($this->getTargetTable(), $targetKey)
	            );

	            $this->applyWhere($join, $relation, $this->getTargetTable(), 2);
	        });
    	}
	}

	public function solveColumnWhere($alias, $tableName, $column)
	{
		return $this->parseAliasableKey($this->getTargetTable(), $column);
	}
}