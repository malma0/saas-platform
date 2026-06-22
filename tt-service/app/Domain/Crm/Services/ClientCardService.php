<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\Client;
use App\Domain\Booking\Models\Booking;
use App\Support\Enums\BookingStatus;
use App\Support\Money\Money;
use Illuminate\Support\Collection;

/**
 * Агрегирует данные карточки клиента:
 *  - История броней
 *  - Статистика (визиты, отмены, сумма)
 *  - Заметки + теги
 */
class ClientCardService
{
    /**
     * Полная карточка клиента.
     *
     * @return array{
     *   client: Client,
     *   stats: array{total_bookings: int, completed: int, cancelled: int, no_show: int, total_spent: int, currency: string},
     *   recent_bookings: Collection,
     *   notes: Collection,
     *   tags: Collection,
     * }
     */
    public function card(Client $client): array
    {
        $client->load(['notes.author', 'tags', 'preferences']);

        $bookings = Booking::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->whereNull('deleted_at')
            ->orderByDesc('start_at')
            ->get();

        $stats = $this->buildStats($bookings, $client);

        return [
            'client'          => $client,
            'stats'           => $stats,
            'recent_bookings' => $bookings->take(20),
            'notes'           => $client->notes,
            'tags'            => $client->tags,
        ];
    }

    private function buildStats(Collection $bookings, Client $client): array
    {
        $currency = 'RUB'; // TODO: из настроек клуба

        $completed = $bookings->where('status', BookingStatus::Completed)->count();
        $cancelled = $bookings->where('status', BookingStatus::Cancelled)->count();
        $noShow    = $bookings->where('status', BookingStatus::NoShow)->count();

        $totalSpent = $bookings
            ->where('status', BookingStatus::Completed)
            ->sum('amount_minor');

        return [
            'total_bookings' => $bookings->count(),
            'completed'      => $completed,
            'cancelled'      => $cancelled,
            'no_show'        => $noShow,
            'total_spent'    => (int) $totalSpent,
            'currency'       => $currency,
        ];
    }
}
