<?php

namespace Railken\EloquentMapper;

use Closure;
use Illuminate\Support\Facades\Cache;
use Railken\Bag;

class Mapper
{
    public static $relations = [];

    public static function addRelation(string $name, Closure $callback)
    {
        if (!isset(static::$relations['*'])) {
            static::$relations['*'] = [];
        }

        static::$relations['*'][] = $name;

        \Illuminate\Database\Eloquent\Builder::macro($name, function () use ($name, $callback) {
            unset(static::$macros[$name]);

            return $callback;
        });
    }

    public static function findRelationByKey(array $relations, string $needle)
    {
        foreach ($relations as $key => $relation) {
            if ($needle === $key) {
                return $relation;
            }
        }

        return null;
    }

    public static function isValidNestedRelation(string $class, string $key)
    {
        $keys = explode(".", $key);

        $relation = static::findRelationByKey(static::relations($class), $keys[0]);

        if (!$relation) {
            return false;
        }

        if (count($keys) === 1) {
            return true;
        }
        
        array_shift($keys);

        return static::isValidNestedRelation($relation->get('model'), implode(".", $keys));
    }

    public static function relations(string $class)
    {
        $cacheKey = sprintf('relations:%s', $class);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $finder = new RelationFinder();
        $relations = $finder->getModelRelations($class)->toArray();

        foreach ($relations as $key => $relation) {
            $class = $relation->getModel();

            if ($relation->getType() === 'MorphTo') {
                unset($relations[$key]);
            } else {
                $bag = new Bag([
                    'type'       => $relation->getType(),
                    'name'       => $relation->getName(),
                    'model'      => $relation->getModel(),
                    'localKey'   => $relation->getLocalKey(),
                    'foreignKey' => $relation->getForeignKey()
                ]);

                $relations[$key] = $bag;
            }
        }

        foreach ($relations as $key => $relation) {
            if (!static::findSameRelation($relations, $relation)) {
                $bag->set('children', static::relations($relation->getModel()));
            }
        }

        Cache::forever($cacheKey, $relations);

        return $relations;
    }

    public static function findSameRelation(array $relations, Bag $needle) {
        foreach ($relations as $key => $relation) {
            $bag = (new Bag($relation->toArray()))->remove('children');
            
            if (count(array_diff($bag->toArray(), $needle->toArray())) === 0) {
                return true;
            }

            if ($relation->children && static::findSameRelation($relation->children, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function mapKeysRelation(string $class)
    {
        return static::mapRelations($class, function ($prefix, $relation) {
            $key = $prefix ? $prefix.'.'.$relation->name : $relation->name;

            return [$key, [$key]];
        });
    }

    public static function mapRelations(string $class, Closure $parser)
    {
        $relations = static::relations($class);

        $closure = function ($relations, $prefix = '') use (&$closure, $parser) {
            $keys = [];

            foreach ((array) $relations as $relation) {
                list($newPrefix, $newKeys) = $parser($prefix, $relation);

                if ($newPrefix !== null) {
                    $keys = array_merge($keys, $newKeys, $closure($relation->children, $newPrefix));
                }
            }

            return $keys;
        };

        return $closure($relations);
    }
}
