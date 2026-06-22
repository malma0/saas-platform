<?php

namespace App\Domain\Payments\Models;

use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_id',
        'type',
        'amount_minor',
        'currency_code',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload'  => 'array',
            'amount_minor' => 'integer',
        ];
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
