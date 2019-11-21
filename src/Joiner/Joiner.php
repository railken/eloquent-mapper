<?php

namespace Railken\EloquentMapper\Joiner;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Railken\EloquentMapper\Contracts\Joiner as JoinerContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations;
use Railken\EloquentMapper\Joiner\Resolvers;

class Joiner implements JoinerContract
{
    /**
     * Construct
     */
    public function __construct()
    {
        $this->resolvers = new Collection();
        $this->resolvers = $this->resolvers->merge([
            Relations\BelongsTo::class => Resolvers\BelongsTo::class,

            Relations\BelongsToMany::class => Resolvers\BelongsToMany::class,
            Relations\MorphToMany::class => Resolvers\BelongsToMany::class,
            
            Relations\HasOneOrMany::class => Resolvers\HasOneOrMany::class,
            Relations\MorphMany::class => Resolvers\HasOneOrMany::class,
            Relations\MorphOne::class => Resolvers\HasOneOrMany::class,
            Relations\MorphOneOrMany::class => Resolvers\HasOneOrMany::class,
        ]);
    }

    /**
     * Retrieve resolvers
     *
     * @return \Illuminate\Support\Collection
     */
    public function getResolvers(): Collection
    {
        return $this->resolvers;
    }

    /**
     * Perform a join based on the name of the said relation
     *
     * @param $builder
     * @param $model
     * @param $relations
     */
    public function join($builder, $relation, $model = null, $method = 'leftJoin')
    {
        $model = $model ?? $builder->getModel();

        if ($builder instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
            $builder = $builder->getQuery();
        }
        

        $relations = explode('.', $relation);

        $currentTableAlias = $model->getTable();
        $basePrimaryKey = $model->getKeyName();

        $currentModel = $model;

        foreach ($relations as $i => $relation) {

            $relatedRelation   = $currentModel->$relation();
            $relatedModel      = $relatedRelation->getRelated();
            $relatedTable      = $relatedModel->getTable();
            $relationName      = $this->parseAlias(array_slice($relations, 0, $i + 1));

            $joinQuery = $relatedModel->getTable().($relatedModel->getTable() !== $relationName ? ' as '.$relationName : '');

            // @TODO, check if same joinQuery is already in $builder


            foreach ($this->getResolvers()->toArray() as $key => $resolverClass) {

                if ($relatedRelation instanceof $key) {

                    $resolver = new $resolverClass;

                    $resolver
                        ->setRelationName($relationName)
                        ->setMethod($method)
                        ->setJoinQuery($joinQuery)
                        ->setRelation($relatedRelation)
                        ->setSourceTable($currentTableAlias)
                        ->setTargetTable($relationName)
                    ;

                    $resolver->resolve($builder);

                    break; //uhm..........
                }
            }

            $currentModel      = $relatedModel;
            $currentTableAlias = $relationName;
        }
    }

    public function leftJoin($builder, $relation, $model = null)
    {
        return $this->join($builder, $relation, $model, 'leftJoin');
    }

    protected function parseAlias(array $relations): string
    {
        return implode('.', $relations);
    }
}
