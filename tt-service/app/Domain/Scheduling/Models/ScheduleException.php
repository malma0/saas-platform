<?php

namespace App\Domain\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    protected $fillable = [
        'schedule_template_id',
        'session_date',
        'type',       // cancelled | moved
        'new_start_at',
        'new_end_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'new_start_at' => 'datetime',
            'new_end_at'   => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }

    public function isCancelled(): bool
    {
        return $this->type === 'cancelled';
    }

    public function isMoved(): bool
    {
        return $this->type === 'moved';
    }
}
