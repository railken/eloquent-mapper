<?php

namespace Railken\EloquentMapper\Joiner\Resolvers;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations; // tmp
use Illuminate\Support\Facades\DB;

abstract class Base
{
    protected $relation;
    protected $relationName;
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

    public function setRelationName(string $relationName): Base
    {
        $this->relationName = $relationName;

        return $this;
    }

    public function getRelationName(): string
    {
        return $this->relationName;
    }

    public function getJoinQuery(): string
    {
        $table = $this->getRelation()->getRelated()->getTable();

        return $table === $this->getRelationName() ? $table : $table.' as '.$this->getRelationName();
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

    public function isAlreadyJoined($builder, $query)
    {
        foreach ((array) $builder->getQuery()->joins as $join) {
            if ($join->table === $query) {
                return true;
            }
        }

        return false;
    }

    public function applyWhereFromArray($join, $alias, array $wheres)
    {
        foreach ($wheres as $clause) {
            $method = 'Basic' === $clause['type'] ? 'where' : 'where'.$clause['type'];


            if ($clause['type'] === 'Nested') {
                $prefix = $clause['boolean'] ?? 'and';
                $method = $clause['boolean'] === 'and' ? 'where' : $clause['boolean'].'Where';

                $join->$method(function ($query) use ($alias, $clause) {
                    return $this->applyWhereFromArray($query, $alias, $clause['query']->wheres);
                });
            }
            
            if (isset($clause['column'])) {

                // Remove first alias table name
                $partsColumn = explode('.', $clause['column']);

                if (count($partsColumn) > 1) {
                    $column = implode('.', array_slice($partsColumn, 1));
                    $clause['column'] = $this->solveColumnWhere($alias, $partsColumn[0], $column);

                    unset($clause['type']);
                    
                    $join->$method(...array_values($clause));
                } else {
                    throw new \Exception(sprintf("All columns should have a table alias refering, %s", implode(".", $partsColumn)));
                }
            }
        }
    }

    public function applyWhere($join, $relation, $alias)
    {
        $relationBuilder = $relation->getQuery();
        $relationBuilder = $relationBuilder->applyScopes();

        $wheres = $relationBuilder->getQuery()->wheres;

        $this->applyWhereFromArray($join, $alias, $wheres);
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

        $reflection = new \ReflectionClass($relation);
        $property = $reflection->getProperty($keyName);
        $property->setAccessible(true);
        return $property->getValue($relation);

        throw new \Exception(sprintf("Cannot retrieve %s from %s", $keyName, get_class($relation)));
    }

    protected function parseAliasableKey(string $alias, string $key)
    {
        return DB::raw('`'.$alias.'`.`'.$key.'`');
    }

    public function join($builder, string $sourceKey, string $targetKey)
    {
        $method = $this->getMethod();
        
        $builder->$method($this->getJoinQuery(), function ($join) use ($targetKey, $sourceKey) {
            $relation = $this->getRelation();

            $targetKey = $this->getKeyFromRelation($relation, $targetKey);
            $sourceKey = $this->getKeyFromRelation($relation, $sourceKey);

            $join->on(
                $this->parseAliasableKey($this->getSourceTable(), $sourceKey),
                '=',
                $this->parseAliasableKey($this->getTargetTable(), $targetKey)
            );

            $this->applyWhere($join, $relation, null);
        });
    }
    
    abstract public function resolve(Builder $builder);

    public function toArray(): array
    {
        return [
            'relationName' => $this->getRelationName(),
            'relation' => $this->getRelation(),
            'method' => $this->getMethod(),
            'joinQuery' => $this->getJoinQuery(),
            'sourceTable' => $this->getSourceTable(),
            'targetTable' => $this->getTargetTable()
        ];
    }


    public function solveColumnWhere($alias, $tableName, $column)
    {
        return $this->parseAliasableKey($this->getTargetTable(), $column);
    }
}
