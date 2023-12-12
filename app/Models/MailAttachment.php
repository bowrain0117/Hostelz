<?php

namespace App\Models;

use App\Jobs\MailAttachmentDelete;
use Exception;
use Illuminate\Support\Facades\Storage;
use Lib\BaseModel;

class MailAttachment extends BaseModel
{
    protected $table = 'mailAttachment';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public const STORAGE_FOLDER = 'mail-attachments';

    public const STORAGE_DISK = 'spaces1';

    /**
     * We do the mail attachment delete in a queue because it may not work when the
     * cloud storage is offline, so the queue lets us retry it until it works.
     * Also makes deleting mail faster.
     **/
    public function queuedDelete(): void
    {
        dispatch(new MailAttachmentDelete($this));
    }

    public function delete(): void
    {
        $result = self::storageDisk()->delete($this->getRelativeFilePath());
        if (! $result) {
            logError("Error deleting attachment (email:{$this->mailID};attachment:{$this->id}) data.");
        }

        parent::delete();
    }

    /* Misc */

    public function getRelativePath()
    {
        if (! $this->id) {
            throw new Exception('ID not yet set.');
        }

        return self::STORAGE_FOLDER . '/' . ($this->id % 100);
    }

    public function getRelativeFilePath()
    {
        return $this->getRelativePath() . '/' . $this->id;
    }

    public function saveContentsFromFile($filePath)
    {
        if (! $this->id) {
            $this->save();
        } // have to save it first so it has an ID for getFilePath() to use

        $result = self::storageDisk()->put($this->getRelativeFilePath(), fopen($filePath, 'r'), 'private');
        if (! $result) {
            throw new Exception('Error saving attachment data.');
        }

        $this->size = filesize($filePath);
        $this->setMissingValuesAndSanitize();
        $this->save();

        return $this; // for chaining
    }

    public function saveContentsFromString($data)
    {
        if (! $this->id) {
            $this->save();
        } // have to save it first so it has an ID for getFilePath() to use

        $result = self::storageDisk()->put($this->getRelativeFilePath(), $data, 'private');
        if (! $result) {
            throw new Exception('Error saving attachment data.');
        }

        $this->size = strlen($data);
        $this->setMissingValuesAndSanitize();
        $this->save();

        return $this; // for chaining
    }

    public function setMissingValuesAndSanitize(): void
    {
        $this->filename = sanitizeFilename($this->filename);
        if ($this->filename == '') {
            $this->filename = 'untitled';
        }

        if (! $this->size) {
            $this->size = self::storageDisk()->size($this->getRelativeFilePath());
        }
        if ($this->mimeType == '') {
            $this->mimeType = determineMimeTypeForFilename($this->filename);
        }
    }

    public function getContents()
    {
        return self::storageDisk()->get($this->getRelativeFilePath());
    }

    private static function storageDisk()
    {
        // "max 200 requests per second"
        // we were getting a "503 Slow Down" errors.
        // Still getting them with 10 miliseconds, so trying 50.
        minDelayBetweenCalls('MailAttachment:storageApiCalls', 50); // (miliseconds)

        return Storage::disk(self::STORAGE_DISK);
    }

    /* Relationships */

    public function mailMessage()
    {
        return $this->belongsTo(\App\Models\MailMessage::class, 'mailID');
    }
}
