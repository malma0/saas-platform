<?php

namespace App\Domain\Payments\Models;

use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Symfony\Component\Uid\Ulid;

class Refund extends Model
{
    use LogsActivity;

    protected $fillable = [
        'public_id',
        'payment_id',
        'amount_minor',
        'currency_code',
        'reason',
        'status',
        'provider_refund_id',
        'initiated_by',
    ];

    protected function casts(): array
    {
        return [
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
     * Аудит (Правило 5): фиксируем создание/изменение возврата.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('refund')
            ->logOnly(['status', 'amount_minor', 'currency_code', 'reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function money(): Money
    {
        return Money::of($this->amount_minor, $this->currency_code);
    }
}
