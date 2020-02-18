<?php

namespace Railken\EloquentMapper\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Map
{
    /**
     * Return an array of all models you want to map
     *
     * @return array
     */
    public function models(): array;
    
    /**
     * Given an instance of the model, retrieve all the relations
     *
     * @return array
     */
    public function relations(Model $model): array;

    /**
     * Given an instance of the model, retrieve all the attributes
     *
     * @return array
     */
    public function attributes(Model $model): array;

    /**
     * Convert a model to a unique key
     *
     * @param Model $model
     *
     * @return string
     */
    public function modelToKey(Model $model): string;

    /**
     * Convert key to a new instance of a model
     *
     * @param string $key
     *
     * @return Model
     */
    public function keyToModel(string $key): Model;
}
