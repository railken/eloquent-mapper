<?php

namespace Railken\EloquentMapper;

use Railken\EloquentMapper\Contracts\Map as MapContract;
use Railken\EloquentMapper\Relations\RelationFinder;
use Illuminate\Database\Eloquent\Model;

class Map implements MapContract
{
    /**
     * An array containing all models
     *
     * @var array
     */
    protected $models = [];

    /**
     * Add a new model to scan
     *
     * @param string $class
     */
    public function addModel(string $class)
    {
        $this->models[] = $class;
    }

    /**
     * Return an array of all models you want to map
     *
     * @return array
     */
    public function models(): array 
    {
        return $this->models;
    }
    
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
        return $model->getCasts();
    }
}
