<?php

namespace App\Domain\Booking\Models;

use App\Support\Enums\BookingStatus;
use App\Support\Money\Money;
use App\Support\Tenancy\BelongsToTenant;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Attendance\Models\Attendance;
use App\Domain\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Symfony\Component\Uid\Ulid;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use BelongsToTenant, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'public_id',
        'club_id',
        'branch_id',
        'service_offering_id',
        'client_id',
        'admin_id',
        'start_at',
        'end_at',
        'status',
        'amount_minor',
        'currency_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_at'     => 'datetime',
            'end_at'       => 'datetime',
            'status'       => BookingStatus::class,
            'amount_minor' => 'integer',
        ];
    }

    protected static function newFactory(): BookingFactory
    {
        return BookingFactory::new();
    }

    /**
     * Аудит (Правило 5): фиксируем изменения брони — статус, время, сумма, отмена.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('booking')
            ->logOnly(['status', 'start_at', 'end_at', 'amount_minor', 'currency_code', 'client_id', 'notes', 'deleted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Booking $booking) {
            if (empty($booking->public_id)) {
                $booking->public_id = strtolower((string) new Ulid());
            }
            // Автозаполнение club_id из филиала, если не задан явно
            if (empty($booking->club_id) && !empty($booking->branch_id)) {
                $booking->club_id = \App\Domain\Facilities\Models\Branch::find($booking->branch_id)?->club_id;
            }
        });
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function bookingResources(): HasMany
    {
        return $this->hasMany(BookingResource::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->orderBy('created_at');
    }

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Services\Models\ServiceOffering::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Facilities\Models\Branch::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('created_at');
    }

    public function paidPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('status', 'paid');
    }

    // -------------------------------------------------------------------------
    // Money helper
    // -------------------------------------------------------------------------

    public function money(): Money
    {
        return Money::of($this->amount_minor, $this->currency_code);
    }

    // -------------------------------------------------------------------------
    // Статусные хелперы
    // -------------------------------------------------------------------------

    public function isPending(): bool    { return $this->status === BookingStatus::Pending; }
    public function isConfirmed(): bool  { return $this->status === BookingStatus::Confirmed; }
    public function isCancelled(): bool  { return $this->status === BookingStatus::Cancelled; }
    public function isCompleted(): bool  { return $this->status === BookingStatus::Completed; }
    public function isFinal(): bool      { return $this->status->isFinal(); }
}
