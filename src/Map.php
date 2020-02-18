<?php

namespace Railken\EloquentMapper;

use Railken\EloquentMapper\Contracts\Map as MapContract;
use Railken\EloquentMapper\Relations\RelationFinder;
use Illuminate\Database\Eloquent\Model;

class Map implements MapContract
{


    /**
     * Return an array of all models you want to map
     *
     * @return array
     */
    public function models(): array
    {
        return [];
    }
    
    /**
     * Given an instance of the model, retrieve all the relations
     *
     * @return array
     */
    public function relations(Model $model): array
    {
        $finder = new RelationFinder();

        return $finder->getModelRelations($model)->toArray();
    }

    /**
     * Given an instance of the model, retrieve all the attributes
     *
     * @return array
     */
    public function attributes(Model $model): array
    {
        return array_unique(array_merge(
            array_keys($model->getCasts()),
            $model->getFillable(),
            $model->getDates()
        ));
    }

    /**
     * Convert a model to a unique key
     *
     * @param Model $model
     *
     * @return string
     */
    public function modelToKey(Model $model): string
    {
        return get_class($model);
    }

    /**
     * Convert key to a new instance of a model
     *
     * @param string $key
     *
     * @return Model
     */
    public function keyToModel(string $key): Model
    {
        return new $key;
    }
}
