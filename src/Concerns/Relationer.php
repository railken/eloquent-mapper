<?php

namespace Railken\EloquentMapper\Concerns;

use Imanghafoori\Relativity\DynamicRelations;
use Railken\EloquentMapper\Concerns\HasBelongsToOne;
use Railken\EloquentMapper\Concerns\HasMorphToOne;

trait Relationer
{
    use DynamicRelations;
    use HasBelongsToOne;
    use HasMorphToOne;
}
