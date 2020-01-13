<?php

namespace Railken\EloquentMapper\With;

use Ramsey\Collection\AbstractCollection;

class WithItem
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $query;

    /**
     * Construct
     *
     * @param string $name
     * @param string $query
     */
    public function __construct(string $name, string $query = null)
    {
        $this->name = $name;
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
