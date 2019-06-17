<?php

namespace Railken\EloquentMapper;

use Railken\Bag;
use Railken\Cacheable\CacheableContract;
use Railken\Cacheable\CacheableTrait;

class Mapper implements CacheableContract
{
    use CacheableTrait;

    public static $relations = [];

    public static function relations(string $class)
    {
        $finder = new RelationFinder();

        return $finder->getModelRelations($class)->toArray();

        foreach ($relations as $key => $relation) {
            if (!static::findSameRelation($relations, $relation)) {
                $bag->set('children', static::relations($relation->model));
            }
        }

        return $relations;
    }

    public static function findSameRelation(array $relations, Bag $needle)
    {
        foreach ($relations as $key => $relation) {
            $bag = $relation->remove('children');

            if (json_encode($bag->toArray()) === json_encode($needle->toArray())) {
                return true;
            }

            if ($relation->children && static::findSameRelation($relation->children, $needle)) {
                return true;
            }
        }

        return false;
    }
}
