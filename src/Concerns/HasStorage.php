<?php

namespace Railken\EloquentMapper\Concerns;

trait HasStorage
{
    public function getFilePath()
    {
        return base_path('bootstrap/cache/map.php');
    }

    public function initializeStorage()
    {
        $filePath = $this->getFilePath();

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
    }

    public function removeByStorage()
    {
        if (file_exists($this->getFilePath())) {
            unlink($this->getFilePath());
        }
    }

    public function getByStorage()
    {
        if (!file_exists($this->getFilePath())) {
            return [];
        }

        return include $this->getFilePath();
    }

    public function setStorage($content)
    {
        file_put_contents($this->getFilePath(), '<?php return '.var_export($content, true).';');
    }
}