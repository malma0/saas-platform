<?php

namespace App\Domain\Payments\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Хранит входящие webhook-события от платёжных провайдеров.
 *
 * Уникальный ключ (provider, event_id) гарантирует идемпотентность:
 * повторная доставка одного события не приведёт к двойному списанию/возврату.
 */
class ProviderWebhookEvent extends Model
{
    protected $table = 'provider_webhook_events';

    protected $fillable = [
        'provider',
        'event_id',
        'payload',
        'status',
        'error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function markProcessed(): void
    {
        $this->update(['status' => 'processed', 'processed_at' => now()]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => 'failed', 'error' => $error]);
    }

    public function markSkipped(): void
    {
        $this->update(['status' => 'skipped', 'processed_at' => now()]);
    }
}
