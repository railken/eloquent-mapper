<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

class BelongsTo extends Base
{	
	protected $skipClausesByClassRelation = 1;
	
	public function resolve(Builder $builder)
	{
		$method = $this->getMethod();

        $builder->$method($this->getJoinQuery(), function ($join) {

			$relation = $this->getRelation();

	        $targetKey = $this->getKeyFromRelation($relation, 'ownerKey');
	        $sourceKey = $this->getKeyFromRelation($relation, 'foreignKey');

            $join->on(
                $this->parseAliasableKey($this->getSourceTable(), $sourceKey),
                '=', 
                $this->parseAliasableKey($this->getTargetTable(), $targetKey)
            );

            $this->joinQuery($join, $relation, $this->getTargetTable(), $this->getSourceTable());
        });
	}
}