<?php

namespace Railken\EloquentMapper;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Railken\Bag;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class RelationFinder
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
        $macroMethods = $this->getMacroMethods($model);

        $methods = Collection::make($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->merge($traitMethods)
            ->reject(function (ReflectionMethod $method) use ($model) {
                return $method->class !== $model;
            })
            ->mapWithKeys(function (ReflectionMethod $method) {
                return [$method->getName() => $method];
            })
            ->merge($macroMethods)
            ->reject(function (ReflectionFunctionAbstract $functionAbstract) {
                return $functionAbstract->getNumberOfParameters() > 0 || !is_subclass_of((string) $functionAbstract->getReturnType(), Relation::class);
            });
        $relations = Collection::make();

        $methods->map(function (ReflectionFunctionAbstract $functionAbstract, string $functionName) use ($model, &$relations) {
            try {
                $return = $functionAbstract instanceof ReflectionMethod ? $functionAbstract->invoke(app($model)) : (app($model))->$functionName();
                $relations = $relations->merge($this->getRelationshipFromReturn($functionName, $return));
            } catch (\BadMethodCallException $e) {
            }
        });

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

    protected function getMacroMethods(string $model)
    {
        $query = app($model)->newModelQuery();
        $reflection = (new ReflectionClass($query));
        $property = $reflection->getProperty('macros');
        $property->setAccessible(true);

        return Collection::make($property->getValue($query))
            ->map(function ($callable) {
                return new ReflectionFunction($callable);
            });
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

            return [$name => new Bag([
                'type'       => (new ReflectionClass($return))->getShortName(),
                'name'       => $name,
                'model'      => (new ReflectionClass($return->getRelated()))->getName(),
                'localKey'   => $localKey,
                'foreignKey' => $foreignKey,
            ])];
        }
    }
}
