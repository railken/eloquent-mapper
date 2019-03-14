<?php

namespace Railken\EloquentMapper\Concerns;

use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Joiner;

trait MapRelations
{
    public function mapRelations(int $level = 3)
    {
        return Mapper::relations(get_class($this), $level);
    }

    public function mapKeysRelation(int $level = 3)
    {
        return Mapper::mapKeysRelation(get_class($this), $level);
    }

    public function joinRelations(string $relation)
    {
        return Joiner::joinRelations($this->newQuery(), $relation);
    }
}
