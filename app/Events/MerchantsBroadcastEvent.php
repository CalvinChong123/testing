<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MerchantsBroadcastEvent
{
    use Dispatchable, SerializesModels;

    public $onlineMerchants;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $onlineMerchants)
    {
        $this->onlineMerchants = $onlineMerchants;
    }
}
