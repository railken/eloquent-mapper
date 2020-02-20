<?php

namespace Railken\EloquentMapper\Concerns;

use Railken\Bag;

trait HasData
{
    protected $data = [];

    public function setData(string $key, array $data)
    {
        $this->data[$key] = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function removeData($key)
    {
        unset($this->data[$key]);
    }

    public function getDataByKey(string $key)
    {
        return (array) (new Bag($this->data))->get($key, []);
    }
}
