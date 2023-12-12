<?php

namespace App\Events\Import;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InsertImported implements ShouldBroadcast
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
        public bool $isInserted = false
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("import-inserted.{$this->system}");
    }

    public function broadcastAs()
    {
        return 'import.inserted';
    }
}
