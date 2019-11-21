<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

class MorphToMany extends Base
{	
	
	public function resolve(Builder $builder)
	{
		$method = $this->getMethod();
		$table = $this->getRelation()->getTable();
		
		$pivotTableAlias = $this->getPivotTable();
		
		if (!$this->isAlreadyJoined($builder, $table." as ".$pivotTableAlias)) {

			$builder->$method($table." as ".$pivotTableAlias, function ($join) use ($pivotTableAlias) {
				$relation = $this->getRelation();

		        $sourceKey = $this->getKeyFromRelation($relation, 'parentKey');
		        $pivotKey = $this->getKeyFromRelation($relation, 'foreignPivotKey');

	            $join->on(
	                $this->parseAliasableKey($this->getSourceTable(), $sourceKey),
	                '=', 
	                $this->parseAliasableKey($pivotTableAlias, $pivotKey)
	            );

	            $this->applyWhere($join, $relation, $pivotTableAlias, 1, 1);
			});
		}
		
		$table = $this->getRelation()->getRelated()->getTable();

		if (!$this->isAlreadyJoined($builder, $table." as ".$this->getRelationName())) {

			$builder->$method($table." as ".$this->getRelationName(), function ($join) use ($pivotTableAlias) {

				$relation = $this->getRelation();

		        $pivotKey = $this->getKeyFromRelation($relation, 'relatedPivotKey');
		        $targetKey = $this->getKeyFromRelation($relation, 'relatedKey');

	            $join->on(
	                $this->parseAliasableKey($pivotTableAlias, $pivotKey),
	                '=', 
	                $this->parseAliasableKey($this->getTargetTable(), $targetKey)
	            );

	            $this->applyWhere($join, $relation, $pivotTableAlias, 2);
	        });
		}
	}

	public function solveColumnWhere($alias, $tableName, $column)
	{
		// Pivot
		if ($tableName === $this->getRelation()->getTable()) {
			return $this->parseAliasableKey($this->getPivotTable(), $column);
		}

		if ($tableName === $this->getRelation()->getRelated()->getTable()) {
			return $this->parseAliasableKey($this->getRelationName(), $column);
		}
	}

	public function getPivotTable()
	{
		return $this->getRelationName()."_pivot";
	}
}