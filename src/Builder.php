<?php

namespace Railken\EloquentMapper;

use Fico7489\Laravel\EloquentJoin\EloquentJoinBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Builder extends EloquentJoinBuilder
{   
	/**
     * Create a new Eloquent query builder instance.
     *
     * @param mixed $query
     */
    public function __construct($query)
    {
        $this->query = $query;
    }

    protected function parseAlias(Model $relatedModel, array $relations): string
    {
        return implode('.', $relations);
    }

    protected function parseAliasableKey(string $alias, string $key)
    {
        return DB::raw('`'.$alias.'`.`'.$key.'`');
    }
}
