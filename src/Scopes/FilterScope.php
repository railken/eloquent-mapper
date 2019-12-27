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
use Railken\EloquentMapper\Collections\With\WithCollection;

class FilterScope
{
    protected $with;
    protected $keys;
    protected $onApply;
    protected $helper;

    public function __construct()
    {
        $this->helper = app('eloquent.mapper');
        $this->onApply = function($query, $model) { };
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
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $builder
     * @param string $query
     * @param WithCollection $with
     */
    public function apply($builder, string $query, WithCollection $with = null)
    {

        $model = $builder->getModel();

        // Use parser of filter to retrieve nodes
        $filter = new Filter($model->getTable(), ['*']);

        // Retrieve relations used by the query
        $keys = $this->extractFilterKeys($filter->getParser()->parse($query));

        // Extract all relations from keys
        $relations = $this->filterKeysByRelations($builder, $model, $keys);

            
        // Create a correct collection of keys based on relations and exploded attributes
        $keys = $this->explodeKeysWithAttributes($model, $relations);


        if ($with) {
            foreach ($with as $withOne) {

                $resolvedRelations = $this->helper->resolveRelation(get_class($model), $withOne->getName());

                if ($resolvedRelations->count() !== 0) {

                    $resolvedRelation = $resolvedRelations[$withOne->getName()];

                    $builder->with([$withOne->getName() => function ($query) use ($resolvedRelation) {

                        $withModel = new $resolvedRelation->model;
                        $innerScope = new self();
                        $innerScope->setOnApply($this->getOnApply());
                        $query->select($withModel->getTable().".*");
                        $innerScope->onApply($query, $withModel);
                        $innerScope->apply($query, $withOne->getQuery());
                    }]);
                }
            }
        }

        $joiner = app(\Railken\EloquentMapper\Contracts\Joiner::class);

        foreach ($relations as $relation) {
            $joiner->leftJoin($builder, $relation, $model);
        }

        // Use $keys to create a more correct filter
        $filter = new Filter($model->getTable(), $keys->toArray());
        $filter->build($builder, $query);

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

        if ($node instanceof KeyNode) {
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
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $builder
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
            return $this->helper->isValidNestedRelation(get_class($model), $item);
        });
    }
    

    public function explodeKeysWithAttributes(Model $model, Collection $relations): Collection
    {
        $keys = $this->helper->getAttributesByModel($model);

        $relations = $this->helper->resolveRelations(get_class($model), $relations->toArray());

        foreach ($relations as $key => $relation) {

            $attrs = $this->helper->getAttributesByModel(new $relation['model']);

            $keys = $keys->merge($attrs->map(function ($attribute) use ($key) {
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
