<?php

namespace Railken\EloquentMapper;

use Railken\EloquentMapper\Contracts\Map as MapContract;
use Railken\EloquentMapper\Relations\RelationFinder;
use Illuminate\Database\Eloquent\Model;

abstract class Map implements MapContract
{
    /**
     * Return an array of all models you want to map
     *
     * @return array
     */
    abstract public function models(): array;
    
    /**
     * Given an instance of the model, retrieve all the relations
     *
     * @return array
     */
    public function relations(Model $model): array
    {
        $finder = new RelationFinder();

        return $finder->getModelRelations(get_class($model))->toArray();
    }

    /**
     * Given an instance of the model, retrieve all the attributes
     *
     * @return array
     */
    public function attributes(Model $model): array
    {
        return array_merge(
            array_keys($model->getCasts()),
            $model->getFillable(),
            $model->getDates()
        );
    }
}
