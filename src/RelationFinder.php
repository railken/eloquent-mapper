<?php

namespace Railken\EloquentMapper;

use BeyondCode\ErdGenerator\ModelRelation;
use BeyondCode\ErdGenerator\RelationFinder as BaseRelationFinder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class RelationFinder extends BaseRelationFinder
{
    /**
     * Return all relations from a fully qualified model class name.
     *
     * @param string $model
     *
     * @return Collection
     *
     * @throws \ReflectionException
     */
    public function getModelRelations(string $model)
    {
        $class = new ReflectionClass($model);
        $traitMethods = Collection::make($class->getTraits())->map(function (ReflectionClass $trait) {
            return Collection::make($trait->getMethods(ReflectionMethod::IS_PUBLIC));
        })->flatten();
        $methods = Collection::make($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->merge($traitMethods)
            ->reject(function (ReflectionMethod $method) use ($model) {
                return $method->class !== $model || $method->getNumberOfParameters() > 0;
            });
        $relations = Collection::make();

        $methods->map(function (ReflectionMethod $method) use ($model, &$relations) {
            $relations = $relations->merge($this->getRelationshipFromMethodAndModel($method, $model));
        });

        $relations = $relations->merge($this->retrieveMacroableRelationships($model));

        $relations = $relations->filter();
        if ($ignoreRelations = array_get(config('erd-generator.ignore', []), $model)) {
            $relations = $relations->diffKeys(array_flip($ignoreRelations));
        }

        return $relations;
    }

    public function getKeyFromRelation(Relation $relation, string $keyName)
    {
        $getQualifiedKeyMethod = 'getQualified'.ucfirst($keyName).'Name';

        if (method_exists($relation, $getQualifiedKeyMethod)) {
            return last(explode('.', $relation->$getQualifiedKeyMethod()));
        }

        $getKeyMethod = 'get'.ucfirst($keyName);

        if (method_exists($relation, $getKeyMethod)) {
            return $relation->$getKeyMethod();
        }

        // relatedKey is protected before 5.7 in BelongsToMany
        $reflection = new \ReflectionClass($relation);
        $property = $reflection->getProperty($keyName);
        $property->setAccessible(true);

        return $property->getValue($relation);
    }

    /**
     * @param string $qualifiedKeyName
     *
     * @return mixed
     */
    protected function getParentKey(string $qualifiedKeyName)
    {
        $segments = explode('.', $qualifiedKeyName);

        return end($segments);
    }

    protected function retrieveMacroableRelationships(string $model)
    {
        $query = app($model)->newModelQuery();

        $reflection = (new ReflectionClass($query));
        $property = $reflection->getProperty('macros');
        $property->setAccessible(true);

        $relations = Collection::make($property->getValue($query))
            ->map(function ($callable) {
                return new ReflectionFunction($callable);
            })
            ->reject(function ($callable) use ($model) {
                return $callable->getNumberOfParameters() > 0;
            })->mapWithKeys(function ($callable, $methodName) use ($model) {
                return $this->getRelationshipFromReturn($methodName, (app($model))->$methodName());
            });

        return $relations;
    }

    /**
     * @param ReflectionMethod $method
     * @param string           $model
     *
     * @return array|null
     */
    protected function getRelationshipFromMethodAndModel(ReflectionMethod $method, string $model)
    {
        try {
            $return = $method->invoke(app($model));

            return $this->getRelationshipFromReturn($method->getName(), $return);
        } catch (\Throwable $e) {
        }

        return null;
    }

    protected function getRelationshipFromReturn(string $name, $return)
    {
        if ($return instanceof Relation) {
            $localKey = null;
            $foreignKey = null;
            if ($return instanceof HasOneOrMany) {
                $localKey = $this->getKeyFromRelation($return, 'parentKey');
                $foreignKey = $this->getKeyFromRelation($return, 'foreignKey');
            }
            if ($return instanceof BelongsTo) {
                $foreignKey = $this->getKeyFromRelation($return, 'ownerKey');
                $localKey = $this->getKeyFromRelation($return, 'foreignKey');
            }

            return [
                $name => new ModelRelation(
                    $name,
                    (new ReflectionClass($return))->getShortName(),
                    (new ReflectionClass($return->getRelated()))->getName(),
                    $localKey,
                    $foreignKey
                ),
            ];
        }
    }
}
