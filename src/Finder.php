<?php

namespace Railken\EloquentMapper;

use Closure;
use Illuminate\Support\Collection;

class Finder
{
    protected $data;

    public function __construct(array $data)
    {
        array_walk($data, function (&$item, $key) {
            $item = array_map(function ($x) {
                return (object) $x;
            }, $item);
        });

        $this->data = $data;
    }

    public function findRelationByKey(array $relations, string $needle)
    {
        foreach ($relations as $relation) {
            if ($needle === $relation->name) {
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
            $relation = $this->findRelationByKey($this->relations($class), $key);

            if (!$relation) {
                return Collection::make();
            }

            $class = $relation->model;

            $resolved[implode('.', array_slice($keys, 0, $i + 1))] = $relation;
        }

        return $resolved;
    }

    public function isValidNestedRelation(string $class, string $key)
    {
        return $this->resolveRelation($class, $key)->count() !== 0;
    }

    public function data()
    {
        return $this->data;
    }

    public function removeData($key)
    {
        unset($this->data[$key]);
    }

    public function relations(string $class)
    {
        return isset($this->data[$class]) ? $this->data[$class] : [];
    }

    public function mapKeysRelation(string $class)
    {
        return $this->mapRelations($class, function ($prefix, $relation) {
            $key = $prefix ? $prefix.'.'.$relation->name : $relation->name;

            return [$key, [$key]];
        });
    }

    public function mapRelations(string $class, Closure $parser)
    {
        $relations = $this->relations($class);

        $closure = function ($relations, $prefix = '') use (&$closure, $parser) {
            $keys = [];

            foreach ((array) $relations as $relation) {
                list($newPrefix, $newKeys) = $parser($prefix, $relation);

                /*if ($newPrefix !== null) {
                    $keys = array_merge($keys, $newKeys, $closure($relation->children, $newPrefix));
                }*/
            }

            return $keys;
        };

        return $closure($relations);
    }
}
