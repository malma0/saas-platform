<?php

namespace App\Domain\Attendance\Models;

use App\Domain\Booking\Models\Booking;
use App\Domain\Crm\Models\Client;
use App\Models\User;
use App\Support\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'booking_id',
        'client_id',
        'status',
        'marked_by',
        'marked_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'status'    => AttendanceStatus::class,
            'marked_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPresent(): bool
    {
        return $this->status === AttendanceStatus::Present;
    }

    public function isNoShow(): bool
    {
        return $this->status === AttendanceStatus::NoShow;
    }
}
