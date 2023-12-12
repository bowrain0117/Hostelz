<?php

namespace App\Events\Import;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $queue = 'import';

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public string $system,
        public array $options = []
    ) {
    }

    public function broadcastOn()
    {
        return new PrivateChannel("import-finished.{$this->system}");
    }

    public function broadcastAs()
    {
        return 'import.finished';
    }
}
