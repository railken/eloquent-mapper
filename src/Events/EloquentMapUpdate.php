<?php

namespace Railken\EloquentMapper\Events;

class EloquentMapUpdate
{
	/**
	 * @var string
	 */
	public $model;

	/**
	 * @param string $model
	 */
	public function __construct(string $model)
	{
		$this->model = $model;
	}
}
