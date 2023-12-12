<?php

namespace App\Events;

use App\Models\AttachedText;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttachedTextUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AttachedText $attachedText
    ) {
    }
}
