<?php

namespace App\Jobs;

use App\Models\MailAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * We do the mail attachment delete in a queue because it may not work when the
 * cloud storage is offline, so the queue lets us retry it until it works.
 * Also makes deleting mail faster.
 **/
class MailAttachmentDelete implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $attachment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MailAttachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->attachment->delete();
    }
}
