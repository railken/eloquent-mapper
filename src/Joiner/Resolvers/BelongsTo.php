<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

class BelongsTo extends Base
{
	public function resolve(Builder $builder)
	{
		$method = $this->getMethod();

		if (!$this->isAlreadyJoined($builder, $this->getJoinQuery())) {

	        $builder->$method($this->getJoinQuery(), function ($join) {

				$relation = $this->getRelation();

		        $targetKey = $this->getKeyFromRelation($relation, 'ownerKey');
		        $sourceKey = $this->getKeyFromRelation($relation, 'foreignKey');

	            $join->on(
	                $this->parseAliasableKey($this->getSourceTable(), $sourceKey),
	                '=', 
	                $this->parseAliasableKey($this->getTargetTable(), $targetKey)
	            );

	            $this->applyWhere($join, $relation, null, 1);
	        });
    	}
	}

	public function solveColumnWhere($alias, $tableName, $column)
	{
		return $this->parseAliasableKey($this->getTargetTable(), $column);
	}
}