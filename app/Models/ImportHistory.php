<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'options' => AsArrayObject::class,
        'finished_at' => 'datetime:Y-m-d H:i',
    ];

    public function isActive(): bool
    {
        return $this->isStarted() && ! $this->isCancelled() && ! $this->isFinished();
    }

    public function isStarted(): bool
    {
        return $this->started_at !== null;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    public function getOption(string $key): mixed
    {
        if (empty($this->options[$key])) {
            return null;
        }

        return $this->options[$key];
    }

    public function scopeLastBySystem($query, $system)
    {
        return $query->whereSystem($system)
            ->latest('started_at');
    }

    public static function init(string $system): void
    {
        self::create([
            'system' => $system,
            'started_at' => now(),
            'options' => ['batchId' => '', 'totalPages' => 0, 'lastPage' => 0],
        ]);
    }
}
