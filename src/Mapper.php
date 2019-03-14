<?php

namespace Railken\EloquentMapper;

use BeyondCode\ErdGenerator\RelationFinder;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Support\Facades\Cache;
use Railken\Bag;

class Mapper
{
    public static function mapRelations(string $class, int $level = 3)
    {
        $cacheKey = sprintf('relations:%s', $class);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        if ($level <= 0) {
            return [];
        }

        $finder = new RelationFinder();
        $relations = $finder->getModelRelations($class);

        foreach ($relations as $key => $relation) {
            $class = $relation->getModel();

            $relations[$key] = new Bag([
                'type'       => $relation->getType(),
                'name'       => $relation->getName(),
                'model'      => $relation->getModel(),
                'localKey'   => $relation->getLocalKey(),
                'foreignKey' => $relation->getForeignKey(),
                'children'   => (new $class())->mapRelations($level - 1),
            ]);
        }

        Cache::forever($cacheKey, $relations);

        return $relations;
    }

    public static function mapKeysRelation(string $class, int $level = 3)
    {
        $relations = static::mapRelations($class, $level);

        $closure = function ($relations, $prefix = '') use (&$closure) {
            $keys = [];

            foreach ($relations as $relation) {
                $key = $prefix ? $prefix.'.'.$relation->name : $relation->name;

                $keys[] = $key;

                $keys = array_merge($keys, $closure($relation->children, $key));
            }

            return $keys;
        };

        return $closure($relations);
    }
}
