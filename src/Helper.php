<?php

namespace Railken\EloquentMapper;

use Closure;
use Illuminate\Support\Str;
use Railken\EloquentMapper\Contracts\Map as MapContract;
use Illuminate\Database\Eloquent\Model;
use Railken\EloquentMapper\Concerns\HasStorage;
use Railken\EloquentMapper\Concerns\HasData;
use Illuminate\Support\Collection;

class Helper
{
    use HasStorage;
    use HasData;

    public $map;

    public function __construct(MapContract $map)
    {
        $this->map = $map;
    }

    public function setDataByStorage()
    {
        $this->setData($this->getByStorage());
    }

    public function boot()
    {
        if ($this->initializeStorage()) {
            $this->regenerate();
        } else {
            $this->setDataByStorage();
        }
    }

    public function regenerate()
    {
        foreach ($this->map->models() as $model) {
            $this->generateModel($model instanceof Model ? $model : new $model);
        }

        $this->setDataByStorage();
    }

    public function generateModel(Model $model)
    {
        $content = $this->getByStorage();

        if (!is_array($content) || count($content) === 0) {
            $content = [];
        }

        $attributes = $this->map->attributes($model);

        $relations = collect($this->map->relations($model))->map(function ($relation, $key) {
            return array_merge($relation->toArray(), ['key' => $key]);
        })->values()->toArray();

        $content[get_class($model)] = [
            'relations' => $relations,
            'attributes' => $attributes,
        ];

        $this->setStorage($content);
    }

    public function getAttributesByModel(Model $model)
    {
        return collect($this->getDataByKey(get_class($model).'.attributes'));
    }

    public function findRelationByKey(array $relations, string $needle)
    {
        foreach ($relations as $relation) {
            if ($needle === $relation['name']) {
                return $relation;
            }
        }

        return null;
    }

    public function resolveRelations(Model $model, array $relations)
    {
        $resolved = Collection::make();

        foreach ($relations as $relation) {
            $resolved = $resolved->merge($this->resolveRelation($model, $relation));
        }

        return $resolved;
    }

    public function resolveRelation(Model $model, string $key)
    {
        $resolved = Collection::make();

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {

            $relation = $this->findRelationByKey($this->getDataByKey($this->map->modelToKey($model) . '.relations'), $key);

            if (!$relation) {
                return Collection::make();
            }

            $model = $this->map->keyToModel($relation['related']);

            $resolved[implode('.', array_slice($keys, 0, $i + 1))] = $relation;
        }

        return $resolved;
    }

    public function isValidNestedRelation(Model $model, string $key)
    {
        return $this->resolveRelation($model, $key)->count() !== 0;
    }
}
