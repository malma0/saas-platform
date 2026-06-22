<?php

namespace App\Domain\Booking\Models;

use App\Support\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusHistory extends Model
{
    protected $table = 'booking_status_history';

    public $timestamps = false;

    protected $fillable = ['booking_id', 'status', 'changed_by', 'comment', 'created_at'];

    protected function casts(): array
    {
        return [
            'status'     => BookingStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}
