<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

class BelongsToMany extends Base
{	
	
	public function resolve(Builder $builder)
	{
		$method = $this->getMethod();
		$table = $this->getRelation()->getTable();
		
		$pivotTableAlias = $this->getRelationName()."_pivot";
		
		$builder->$method($table." as ".$pivotTableAlias, function ($join) use ($pivotTableAlias) {
			$relation = $this->getRelation();

	        $sourceKey = $this->getKeyFromRelation($relation, 'relatedKey');
	        $pivotKey = $this->getKeyFromRelation($relation, 'relatedPivotKey');

            $join->on(
                $this->parseAliasableKey($this->getSourceTable(), $sourceKey),
                '=', 
                $this->parseAliasableKey($pivotTableAlias, $pivotKey)
            );

            $this->applyWhere($join, $relation, $pivotTableAlias, $this->getSourceTable(), 1, 1);
		});

		$table = $this->getRelation()->getRelated()->getTable();

		$builder->$method($table." as ".$this->getRelationName(), function ($join) use ($pivotTableAlias) {

			$relation = $this->getRelation();

	        $pivotKey = $this->getKeyFromRelation($relation, 'foreignPivotKey');
	        $targetKey = $this->getKeyFromRelation($relation, 'parentKey');

            $join->on(
                $this->parseAliasableKey($pivotTableAlias, $pivotKey),
                '=', 
                $this->parseAliasableKey($this->getTargetTable(), $targetKey)
            );

            $this->applyWhere($join, $relation, $pivotTableAlias, $this->getTargetTable(), 2);
        });

	}
}