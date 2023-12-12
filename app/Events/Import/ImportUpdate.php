<?php

namespace App\Events\Import;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $queue = 'import';

    public function __construct()
    {
    }

    public function broadcastOn()
    {
        return new PrivateChannel('import-update');
    }

    public function broadcastAs()
    {
        return 'import.update';
    }
}
