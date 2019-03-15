<?php

namespace Railken\EloquentMapper;

use BeyondCode\ErdGenerator\RelationFinder;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Support\Facades\Cache;
use Railken\Bag;
use Closure;

class Mapper
{
    public static function relations(string $class, int $level = 3)
    {
        $cacheKey = sprintf('relations:%s:%s', $class, $level);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        if ($level <= 0) {
            return [];
        }
        $finder = new RelationFinder();
        $relations = $finder->getModelRelations($class)->toArray();

        foreach ($relations as $key => $relation) {
            $class = $relation->getModel();

            $relations[$key] = new Bag([
                'type'       => $relation->getType(),
                'name'       => $relation->getName(),
                'model'      => $relation->getModel(),
                'localKey'   => $relation->getLocalKey(),
                'foreignKey' => $relation->getForeignKey(),
                'children'   => static::relations($relation->getModel(), $level - 1)
            ]);
        }

        Cache::forever($cacheKey, $relations);

        return $relations;
    }

    public static function mapKeysRelation(string $class, int $level = 3)
    {
        return static::mapRelations($class, function ($prefix, $relation) {

            $key = $prefix ? $prefix.'.'.$relation->name : $relation->name;

            return [$key, [$key]];
        }, $level);
    }

    public static function mapRelations(string $class, Closure $parser, int $level = 3) 
    {
        $relations = static::relations($class, $level);

        $closure = function ($relations, $prefix = '') use (&$closure, $parser) {
            $keys = [];

            foreach ($relations as $relation) {

                list($newPrefix, $newKeys) = $parser($prefix, $relation);

                $keys = array_merge($keys, $newKeys, $closure($relation->children, $newPrefix));
            }

            return $keys;
        };

        return $closure($relations);
    }
}
