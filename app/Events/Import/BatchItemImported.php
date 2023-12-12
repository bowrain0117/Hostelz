<?php

namespace App\Events\Import;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchItemImported implements ShouldBroadcast
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
        public int $page,
    ) {
    }

    public function broadcastOn()
    {
        return new PrivateChannel("import-page-added.{$this->system}");
    }

    public function broadcastAs()
    {
        return 'import.page.added';
    }
}
