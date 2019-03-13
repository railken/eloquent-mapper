<?php

namespace Railken\EloquentMapper\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model 
{
	use \Railken\EloquentMapper\Concerns\MapRelations;
	
	public function books()
	{
		return $this->hasMany(Book::class);
	}

	public function tags()
	{
		return $this->morphMany(Tag::class, 'taggable');
	}
}
