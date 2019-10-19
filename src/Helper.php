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

    public function getFilePath()
    {
        return base_path('bootstrap/cache/relations.php');
    }
}
