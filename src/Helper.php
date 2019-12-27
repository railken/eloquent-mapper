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

    protected $map;

    public function __construct(MapContract $map)
    {   
        $this->map = $map;

        $this->initializeStorage() && $this->boot();

        $this->setData($this->getByStorage());
    }

    public function boot()
    {
        foreach ($this->map->models() as $model) {
            $this->generateModel(new $model);
        }
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

    public function resolveRelations(string $class, array $relations)
    {
        $resolved = Collection::make();

        foreach ($relations as $relation) {
            $resolved = $resolved->merge($this->resolveRelation($class, $relation));
        }

        return $resolved;
    }

    public function resolveRelation(string $class, string $key)
    {
        $resolved = Collection::make();

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {

            $relation = $this->findRelationByKey($this->getDataByKey($class . '.relations'), $key);

            if (!$relation) {
                return Collection::make();
            }

            $class = $relation['model'];

            $resolved[implode('.', array_slice($keys, 0, $i + 1))] = $relation;
        }

        return $resolved;
    }

    public function isValidNestedRelation(string $class, string $key)
    {
        return $this->resolveRelation($class, $key)->count() !== 0;
    }
}
