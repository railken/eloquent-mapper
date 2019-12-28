<?php

namespace Railken\EloquentMapper\Collections\With;

use Ramsey\Collection\AbstractCollection;

class WithCollection extends AbstractCollection
{
    public function getType(): string
    {
        return WithItem::class;
    }
}
