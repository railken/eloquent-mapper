<?php

namespace Railken\EloquentMapper\Concerns;

use Illuminate\Support\Facades\Cache;
use BeyondCode\ErdGenerator\RelationFinder;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Railken\Bag;

trait MapRelations
{
    use EloquentJoin;
    
	public function mapRelations($level = 3)
	{
        $cacheKey = sprintf("relations:%s", get_class($this));

		if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey); 
        }

        if ($level <= 0) {
            return [];
        }

        $finder = new RelationFinder();
        $relations = $finder->getModelRelations(get_class($this));

        foreach ($relations as $key => $relation) {

            $class = $relation->getModel();

            $relations[$key] = new Bag([
                'type' => $relation->getType(),
                'name' => $relation->getName(),
                'model' => $relation->getModel(),
                'localKey' => $relation->getLocalKey(),
                'foreignKey' => $relation->getForeignKey(),
                'children' => (new $class)->mapRelations($level -1)
            ]);
        }

        Cache::forever($cacheKey, $relations);

        return $relations;	
	}

}
