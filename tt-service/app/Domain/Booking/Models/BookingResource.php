<?php

namespace App\Domain\Booking\Models;

use App\Domain\Facilities\Models\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingResource extends Model
{
    protected $fillable = ['booking_id', 'resource_id'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
