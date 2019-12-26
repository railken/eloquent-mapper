<?php

namespace Railken\EloquentMapper\Concerns;

trait HasData
{
    protected $data;

    public function setData(array $data)
    {
        array_walk($data, function (&$item, $key) {
            $item = array_map(function ($x) {
                return (object) $x;
            }, $item);
        });

        $this->data = $data;
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
        return isset($this->data[$key]) ? $this->data[$key] : [];
    }

}