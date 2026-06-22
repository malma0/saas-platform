<?php

namespace App\Domain\Payments\Models;

use App\Domain\Booking\Models\Booking;
use App\Support\Money\Money;
use App\Support\Enums\PaymentStatus;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Symfony\Component\Uid\Ulid;

class Payment extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'public_id',
        'club_id',
        'booking_id',
        'amount_minor',
        'currency_code',
        'status',
        'provider',
        'provider_payment_id',
        'collected_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'       => PaymentStatus::class,
            'amount_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->public_id)) {
                $model->public_id = strtolower((string) new Ulid());
            }
        });
    }

    /**
     * Аудит (Правило 5): фиксируем изменения статуса и суммы платежа.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payment')
            ->logOnly(['status', 'amount_minor', 'currency_code', 'provider'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->orderBy('created_at');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->orderBy('created_at');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function money(): Money
    {
        return Money::of($this->amount_minor, $this->currency_code);
    }

    /** Сумма уже возвращённых средств */
    public function refundedAmount(): int
    {
        return (int) $this->refunds()
            ->where('status', 'completed')
            ->sum('amount_minor');
    }

    public function isPaid(): bool      { return $this->status === PaymentStatus::Paid; }
    public function isPending(): bool   { return $this->status === PaymentStatus::Pending; }
    public function isRefunded(): bool  { return $this->status === PaymentStatus::Refunded; }
}
