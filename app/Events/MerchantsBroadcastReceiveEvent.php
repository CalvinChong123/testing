<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MerchantsBroadcastReceiveEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $merchantId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        log::info('hello world');
        return new Channel('merchants-broadcast-channel');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return ['merchantId' => $this->merchantId];
    }
}
