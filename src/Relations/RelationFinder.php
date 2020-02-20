<?php

namespace Railken\EloquentMapper\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Railken\Bag;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Railken\EloquentMapper\Illuminate\Database\Query\Expression;

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
    public function getModelRelations($model)
    {
        $class = new ReflectionClass($model);

        $traitMethods = Collection::make($class->getTraits())->map(function (ReflectionClass $trait) {
            return Collection::make($trait->getMethods(ReflectionMethod::IS_PUBLIC));
        })->flatten();
        $macroMethods = $this->getMacroMethods(get_class($model));

        $methods = Collection::make($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->merge($traitMethods)
            ->mapWithKeys(function (ReflectionMethod $method) {
                return [$method->getName() => $method];
            })
            ->merge($macroMethods)
            ->reject(function (ReflectionFunctionAbstract $functionAbstract) {
                $type = $functionAbstract->getReturnType();

                return $functionAbstract->getNumberOfParameters() > 0 || !$type || !is_subclass_of($type->getName(), Relation::class);
            });

        $relations = Collection::make();

        $methods = $methods->keys();

        // Detect imanghafoori1/eloquent-relativity
        if ($class->hasProperty('dynamicRelations')) {
            $property = $class->getProperty('dynamicRelations');
            $property->setAccessible(true);

            $methods = $methods->merge(Collection::make($property->getValue('dynamicRelations')->all($model))->keys());
        }
        
        $methods->map(function (string $functionName) use ($model, &$relations) {
            try {
                $return = $model->$functionName();
                $relations = $relations->merge($this->getRelationshipFromReturn($functionName, $return));
            } catch (\BadMethodCallException $e) {
            }
        });

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

        throw new \Exception("Cannot find key named %s in %s", $keyName, get_class($relation));
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

    protected function accessProtected($obj, $prop)
    {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);

        return $property->getValue($obj);
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

            $result = new Bag([
                'related'        => $return->getRelated()->getMorphClass(),
                'type'       => (new ReflectionClass($return))->getShortName(),
                'name'       => $name,
                'model'      => (new ReflectionClass($return->getRelated()))->getName(),
                'localKey'   => $localKey,
                'foreignKey' => $foreignKey,
                'scope'      => $this->getScopeRelation($return),
            ]);

            if ($return instanceof MorphOneOrMany || $return instanceof MorphToMany) {
                $result->set('morphType', $this->getKeyFromRelation($return, 'morphType'));
                $result->set('morphClass', $this->getKeyFromRelation($return, 'morphClass'));
            }

            if ($return instanceof BelongsToMany) {
                $result->set('table', $this->accessProtected($return, 'table'));
                $result->set('intermediate', $this->accessProtected($return, 'using'));
                $result->set('relatedPivotKey', $this->getKeyFromRelation($return, 'relatedPivotKey'));
                $result->set('foreignPivotKey', $this->getKeyFromRelation($return, 'foreignPivotKey'));
            }

            return [$name => $result];
        }
    }

    protected function skipClausesByClassRelation(Relation $relation)
    {
        if ($relation instanceof BelongsTo) {
            return 1;
        }

        if ($relation instanceof HasOneOrMany) {
            return 2;
        }

        if ($relation instanceof MorphToMany) {
            return 1;
        }

        if ($relation instanceof BelongsToMany) {
            return 3;
        }
    }

    protected function getScopeRelation($relation)
    {
        $relationBuilder = $relation->getQuery();

        $wheres = array_slice($relationBuilder->getQuery()->wheres, $this->skipClausesByClassRelation($relation));

        $return = [];

        foreach ($wheres as $n => $clause) {
            if ('Basic' === $clause['type']) {
                if ($n === 0) {
                    $partsColumn = explode('.', $clause['column']);

                    if (count($partsColumn) > 1) {
                        $clause['column'] = implode('.', array_slice($partsColumn, 1));
                    }
                }

                if ($clause['column'] instanceof \Illuminate\Database\Query\Expression) {
                    $clause['column'] = new Expression($clause['column']->getValue());
                }

                $return[] = $clause;
            }
        }

        return $return;
    }
}
