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
use Railken\Bag;
use Illuminate\Support\Collection;

class FilterScope
{
    protected $retriever;
    protected $filter;
    protected $with;
    protected $keys;
    protected $onApply;

    public function __construct(Closure $retriever, string $filter, array $with = [])
    {
        $this->retriever = $retriever;
        $this->filter = $filter;
        $this->with = $this->parseWith($with);
        $this->onApply = function($query, $model) { };
    }

    public function parseWith($with)
    {
        return array_map(function ($element) {

            if (is_array($element)) {
                $element = (object) $element;
            }

            if (is_object($element)) {
                return new Bag([
                    'name' => $element->name,
                    'query' => $element->query
                ]);
            }

            if (is_string($element)) {
                return new Bag([
                    'name' => $element,
                    'query' => ''
                ]);
            }

            return new Bag();
        }, $with);
    }

    public function getOnApply(): Closure
    {
        return $this->onApply;
    }

    public function setOnApply(Closure $onApply)
    {
        $this->onApply = $onApply;
    }

    public function onApply($query, $model)
    {
        $onApply = $this->onApply;
        $onApply($query, $model);
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
        foreach ($this->with as $with) {


            $resolvedRelations = app('eloquent.mapper')->getFinder()->resolveRelation(get_class($model), $with->name);

            if ($resolvedRelations->count() !== 0) {

                $resolvedRelation = $resolvedRelations[$with->name];

                $builder->with([$with->name => function ($query) use ($with, $resolvedRelation) {

                    $withModel = new $resolvedRelation->model;
                    $innerScope = new self($this->retriever, $with->query);
                    $innerScope->setOnApply($this->getOnApply());
                    $query->select($withModel->getTable().".*");
                    $innerScope->onApply($query, $withModel);
                    $innerScope->apply($query, $withModel);
                }]);
            }
        }

        $joiner = app(\Railken\EloquentMapper\Contracts\Joiner::class);

        foreach ($relations as $relation) {
            $joiner->leftJoin($builder, $relation, $model);
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
