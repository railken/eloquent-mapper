<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model 
{
	use \Railken\EloquentMapper\Concerns\MapRelations;

	public function taggable()
	{
		return $this->morphTo();
	}
}
