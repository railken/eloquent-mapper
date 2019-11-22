<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

class HasOneOrMany extends Base
{	
	public function resolve(Builder $builder)
	{
		if (!$this->isAlreadyJoined($builder, $this->getJoinQuery())) {
			$this->join($builder, 'parentKey', 'foreignKey', 2);
    	}
	}

	public function solveColumnWhere($alias, $tableName, $column)
	{
		return $this->parseAliasableKey($this->getTargetTable(), $column);
	}
}