<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations; // tmp
use Illuminate\Support\Facades\DB;

abstract class Base
{   
    protected $relation;
    protected $joinQuery;
    protected $method;
    protected $sourceTable;
    protected $targetTale;

	public function setRelation(Relation $relation): Base
    {   
        $this->relation = $relation;

        return $this;
    }

    public function getRelation(): Relation
    {
        return $this->relation;
    }

    public function setJoinQuery(string $joinQuery): Base
    {   
        $this->joinQuery = $joinQuery;

        return $this;
    }

    public function getJoinQuery(): string
    {
        return $this->joinQuery;
    }

    public function setMethod(string $method): Base
    {
        $this->method = $method;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setSourceTable(string $sourceTable): Base
    {
        $this->sourceTable = $sourceTable;

        return $this;
    }

    public function getSourceTable(): string
    {
        return $this->sourceTable;
    }

    public function setTargetTable(string $targetTale): Base
    {
        $this->targetTale = $targetTale;

        return $this;
    }

    public function getTargetTable(): string
    {
        return $this->targetTale;
    }

    public function joinQuery($join, $relation, $relatedTableAlias, $currentTableAlias)
    {
        /** @var Builder $relationQuery */
        $relationBuilder = $relation->getQuery();

        $wheres = array_slice($relationBuilder->getQuery()->wheres, $this->skipClausesByClassRelation);

        foreach ($wheres as $clause) {
            $method = 'Basic' === $clause['type'] ? 'where' : 'where'.$clause['type'];
            unset($clause['type']);

            // Remove first alias table name
            $partsColumn = explode('.', $clause['column']);

            $tableName = $partsColumn[0];

            if (count($partsColumn) > 1) {
                $clause['column'] = implode('.', array_slice($partsColumn, 1));
            }

            if ($relation instanceof Relations\BelongsToMany && $tableName === $relation->getTable()) {
                $clause['column'] = $this->parseAliasableKey($currentTableAlias, $clause['column']); 
            } else {
                $clause['column'] = $this->parseAliasableKey($relatedTableAlias, $clause['column']); 
            }

            $join->$method(...array_values($clause));
        }

    }

    public function getKeyFromRelation(Relation $relation, string $keyName)
    {
        $getQualifiedKeyMethod = 'getQualified'.ucfirst($keyName).'Name';

        if (method_exists($relation, $getQualifiedKeyMethod)) {
            return last(explode('.', $relation->$getQualifiedKeyMethod()));
        }

        $getKeyMethod = 'get'.ucfirst($keyName);

        if (method_exists($relation, $getKeyMethod)) {
            return $relation->$getKeyMethod();
        }

        throw new \Exception("...");
    }

    protected function parseAliasableKey(string $alias, string $key)
    {
        return DB::raw('`'.$alias.'`.`'.$key.'`');
    }
    
    abstract public function resolve(Builder $builder);

}