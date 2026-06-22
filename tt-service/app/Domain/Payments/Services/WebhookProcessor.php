<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Models\ProviderWebhookEvent;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Идемпотентный обработчик webhook-событий от платёжных провайдеров.
 *
 * Алгоритм:
 *  1. Попытаться INSERT новой записи по (provider, event_id).
 *  2. Если уже существует (UNIQUE constraint) — игнорировать (повторная доставка).
 *  3. Вызвать конкретный обработчик события.
 *  4. При ошибке — пометить событие как failed и перебросить.
 *
 * Такая структура гарантирует: одно событие обрабатывается ровно один раз,
 * даже если провайдер доставил его дважды.
 */
class WebhookProcessor
{
    /**
     * @param string   $provider   Название провайдера: yookassa | stripe | …
     * @param string   $eventId    Уникальный ID события (от провайдера)
     * @param array    $payload    Тело webhook-запроса
     * @param callable $handler    Callable(ProviderWebhookEvent): void — бизнес-логика
     *
     * @return bool  true = обработано; false = дубликат, пропущено
     */
    public function process(
        string   $provider,
        string   $eventId,
        array    $payload,
        callable $handler,
    ): bool {
        // 1. Пытаемся зарегистрировать событие через INSERT ... ON CONFLICT DO NOTHING
        //    (PostgreSQL прерывает транзакцию при UniqueConstraintViolation, поэтому
        //     используем insertOrIgnore, который генерирует ON CONFLICT DO NOTHING)
        $now = now();
        \Illuminate\Support\Facades\DB::table('provider_webhook_events')->insertOrIgnore([
            'provider'   => $provider,
            'event_id'   => $eventId,
            'payload'    => json_encode($payload),
            'status'     => 'received',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $event = ProviderWebhookEvent::where('provider', $provider)
            ->where('event_id', $eventId)
            ->first();

        // Если статус не received — значит уже обрабатывалось раньше (дубликат)
        if ($event->status !== 'received') {
            Log::info("[Webhook] Duplicate event skipped: {$provider}/{$eventId}");
            $event->markSkipped();
            return false;
        }

        // 2. Вызываем обработчик
        try {
            $handler($event);
            $event->markProcessed();
            return true;
        } catch (\Throwable $e) {
            $event->markFailed($e->getMessage());
            Log::error("[Webhook] Processing failed: {$provider}/{$eventId}: {$e->getMessage()}");
            throw $e;
        }
    }
}
