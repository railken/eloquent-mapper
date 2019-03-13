<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model 
{
	use \Railken\EloquentMapper\Concerns\MapRelations;

	public function author()
	{
		return $this->belongsTo(Author::class);
	}

	public function tags()
	{
		return $this->morphMany(Tag::class, 'taggable');
	}
}
