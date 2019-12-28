<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Relation extends MorphPivot
{
    /**
         * Indicates if the IDs are auto-incrementing.
         *
         * @var bool
         */
    public $incrementing = true;

    /**
     * Delete the pivot model record from the database.
     *
     * @return int
     */
    public function delete()
    {
        $query = $this->getDeleteQuery();
        if ($this->morphClass) {
            $query->where($this->morphType, $this->morphClass);
        }
        return $query->delete();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the query builder for a delete operation on the pivot.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getDeleteQuery()
    {
        return $this->id ? $this->newQuery()->where('id', $this->id) : parent::getDeleteQuery();
    }
}
