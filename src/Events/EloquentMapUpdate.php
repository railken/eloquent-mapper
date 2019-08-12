<?php

namespace Railken\EloquentMapper\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EloquentMapUpdate implements ShouldBroadcast
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

    public function broadcastAs()
    {
        return 'update';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('mapper');
    }
}
