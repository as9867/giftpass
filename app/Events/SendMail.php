<?php

namespace App\Events;
use Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
class SendMail extends Event
{
    use SerializesModels;
    public $userId;
    public $mailData;
    public function __construct($userId, $mailData)
    {
        $this->userId = $userId;
        $this->mailData = $mailData;
    }
    public function broadcastOn()
    {
        return [];
    }
}
