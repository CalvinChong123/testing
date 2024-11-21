<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserOnlineStatusEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $userId;

    public $status;

    public function __construct($userId, $status)
    {
        $this->userId = $userId;
        $this->status = $status;
        log::info($userId.':'.$status);
    }

    public function broadcastOn()
    {
        return new PresenceChannel('online-users');
    }
}
