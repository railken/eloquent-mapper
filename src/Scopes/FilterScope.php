<?php

namespace Railken\EloquentMapper\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Railken\LaraEye\Filter;
use Railken\Lem\Contracts\ManagerContract;
use Railken\SQ\Languages\BoomTree\Nodes\KeyNode;
use Railken\SQ\Languages\BoomTree\Nodes\Node;
use Closure;
use Illuminate\Support\Collection;
use Railken\EloquentMapper\Joiner;

class FilterScope
{
    protected $retriever;
    protected $filter;
    protected $with;
    protected $conditionalWith;
    protected $keys;

    public function __construct(Closure $retriever, string $filter, array $with = [], array $conditionalWith = [])
    {
        $this->retriever = $retriever;
        $this->filter = $filter;
        $this->with = $with;
        $this->conditionalWith = $conditionalWith;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\Relation $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function apply($builder, Model $model)
    {
        // Use parser of filter to retrieve nodes
        $filter = new Filter($model->getTable(), ['*']);

        // Retrieve relations used by the query
        $keys = $this->extractFilterKeys($filter->getParser()->parse($this->filter));

        // Extract all relations from keys
        $relations = $this->filterKeysByRelations($builder, $model, $keys);
            
        // Create a correct collection of keys based on relations and exploded attributes
        $keys = $this->explodeKeysWithAttributes($model, $relations);

        // Attach with
        foreach ($this->with as $relation) {

            $resolvedRelations = app('eloquent.mapper')->getFinder()->resolveRelation(get_class($model), $relation);

            if ($resolvedRelations->count() !== 0) {

                $resolvedRelation = $resolvedRelations[$relation];

                $builder->with([$relation => function ($query) use ($relation, $resolvedRelation) {

                    if (isset($this->conditionalWith[$relation])) {
                        $innerScope = new self($this->retriever, $this->conditionalWith[$relation]);
                        $innerScope->apply($query, new $resolvedRelation->model);
                    }

                }]);
            }
        }

        // Create relations based on relations
        $joiner = new Joiner($builder, $model);
        foreach ($relations as $relation) {
            if (!$this->isRelationAlreadyAppliedToBuilder($builder, $relation)) {
                $joiner->joinRelations($relation);
            }
        }

        // Use $keys to create a more correct filter
        $filter = new Filter($model->getTable(), $keys->toArray());
        $filter->build($builder, $this->filter);

        $this->keys = $keys->values()->toArray();
    }

    /**
     * Parse each node to retrieve all "keys" used from the filter
     *
     * @param param Node $node
     *
     * @return Collection
     */
    public function extractFilterKeys($node): Collection
    {
        $relations = collect();

        if ($node instanceof \Railken\SQ\Languages\BoomTree\Nodes\KeyNode) {
            $relations[] = $node->getValue();
        }

        foreach ($node->getChildren() as $child) {
            $relations = $relations->merge($this->extractFilterKeys($child));
        }

        return $relations;
    }

    /**
     * Filter all keys by checking if is a relation with the $model
     *
     * @param \Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\Relation $builder
     * @param Model $model
     * @param Collection $collection
     *
     * @return Collection
     */
    public function filterKeysByRelations($builder, Model $model, Collection $keys): Collection
    {
        return $keys->map(function ($element) {
            return implode('.', array_slice(explode('.', $element), 0, -1));
        })->filter(function ($element) {
            return !empty($element);
        })->filter(function ($item) use ($model) {
            return app('eloquent.mapper')->getFinder()->isValidNestedRelation(get_class($model), $item);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\Relation $builder
     */
    public function isRelationAlreadyAppliedToBuilder($builder, $item)
    {
        $query = $builder->getQuery();

        foreach((array) $query->joins as $joinClause) {
            $table = explode(" as ", $joinClause->table);
            $table = count($table) == 2 ? $table[1] : $table[0];

            if ($table === $item) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve a list of attributes given $model
     *
     * @param Model $model
     *
     * @return Collection
     */
    public function getAttributesByModel(Model $model): Collection
    {
        $retriever = $this->retriever;

        return collect($retriever($model));
    }

    public function explodeKeysWithAttributes(Model $model, Collection $relations): Collection
    {
        $keys = $this->getAttributesByModel($model);

        foreach (app('eloquent.mapper')->getFinder()->resolveRelations(get_class($model), $relations->toArray()) as $key => $relation) {

            $keys = $keys->merge($this->getAttributesByModel(new $relation->model)->map(function ($attribute) use ($key) {
                return $key.'.'.$attribute;
            })->values());
        }

        return $keys;
    }

    /**
     * Retrieve keys
     *
     * @return array
     */
    public function getKeys(): array
    {
        return $this->keys;
    }
}
