<?php

namespace Railken\EloquentMapper;

use Closure;
use Illuminate\Support\Str;

class Helper
{
    protected $retriever;
    protected $finder;

    public function __construct()
    {
        $this->initializeStorage();

        $this->finder = new Finder($this->getRelationsByStorage());

        // Inversed relationships must be reloaded.
        foreach ($this->finder->data() as $model => $relations) {

            if (!class_exists($model)) {

                // not valid anymore.
                $this->removeRelationsByStorage();
                $this->finder->removeData($model);

            } else {
                $this->defineInverseRelationship($relations, $model);
            }
        };
    }

    public function getFinder(): Finder
    {
        return $this->finder;
    }

    public function boot()
    {
        $retriever = $this->retriever;
        $models = $retriever();

        $this->generate($models);
    }

    public function generate(array $models)
    {
        foreach ($models as $model) {
            $this->defineInverseRelationship(Mapper::relations($model), $model);
        }

        foreach ($models as $model) {
            $this->generateModel($model);
        }
    }

    public function retriever(Closure $closure)
    {
        $this->retriever = $closure;

        return $this;
    }

    public function getRelations(string $model)
    {
        return collect(Mapper::relations($model))->map(function ($relation, $key) {
            return array_merge($relation->toArray(), ['key' => $key]);
        })->values();
    }

    public function initializeStorage()
    {
        $filePath = $this->getFilePath();

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
    }

    public function removeRelationsByStorage()
    {
        if (file_exists($this->getFilePath())) {
            unlink($this->getFilePath());
        }
    }

    public function getRelationsByStorage()
    {
        if (!file_exists($this->getFilePath())) {
            return [];
        }

        return include $this->getFilePath();
    }

    public function setRelationsStorage($content)
    {
        file_put_contents($this->getFilePath(), '<?php return '.var_export($content, true).';');
    }

    public function generateModel(string $model)
    {
        $content = $this->getRelationsByStorage();

        if (!is_array($content) || count($content) === 0) {
            $content = [];
        }

        $content[$model] = $this->getRelations($model)->toArray();

        $this->setRelationsStorage($content);
    }

    public function defineInverseRelationship(array $relations, string $model)
    {
        $morphName = $model::getStaticMorphName();

        collect($relations)->map(function ($relation, $key) use ($model, $morphName) {

            $related = $relation->model;

            if (!method_exists($related, 'getDynamicRelations')) {
                return;
            }

            $inflector = new \Doctrine\Common\Inflector\Inflector;
            $methodPlural = $inflector->camelize(Str::plural($morphName));

            if (!method_exists($related, $methodPlural)) {
                if ($relation->type === 'BelongsTo') {
                    $related::has_many($methodPlural, $model);
                }

                if ($relation->type === 'MorphToMany') {
                    if (isset($relation->morphType)) {
                        $key = str_replace('_type', '', $relation->morphType);

                        $newRelation = $related::morphed_by_many($methodPlural, $model, $key, $relation->table, $relation->relatedPivotKey, $relation->foreignPivotKey)
                            ->using($relation->intermediate);

                        foreach (array_slice($relation->scope, 1) as $scope) {
                            $newRelation->where($scope['column'], $scope['operator'], $scope['value']);
                        }
                    }
                }
            }
        });
    }

    public function getFilePath()
    {
        return base_path('bootstrap/cache/relations.php');
    }
}
