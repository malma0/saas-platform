<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Booking\Models\Booking;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Models\Branch;
use App\Http\Controllers\Controller;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Личный кабинет клиента (без логина — вход по номеру телефона).
 *
 * Витрина клиента поверх той же базы: брони, созданные на сайте или заведённые
 * админом в MoonShine, видны здесь. Это «клиентский вид» на общие данные.
 */
class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $branch = Branch::query()->where('is_active', true)->first();
        $tz     = $branch?->timezone ?: 'Europe/Moscow';
        $phone  = trim((string) $request->query('phone', ''));

        $client   = null;
        $upcoming = [];
        $history  = [];

        if ($phone !== '') {
            $client = Client::withoutGlobalScopes()
                ->when($branch, fn ($q) => $q->where('club_id', $branch->club_id))
                ->where('phone', $phone)
                ->first();
        }

        if ($client) {
            $now = Carbon::now('UTC');

            $bookings = Booking::withoutGlobalScopes()
                ->where('client_id', $client->id)
                ->where('status', '!=', BookingStatus::Cancelled->value)
                ->with(['serviceOffering', 'bookingResources.resource'])
                ->orderBy('start_at')
                ->get();

            foreach ($bookings as $b) {
                $row = $this->toRow($b, $tz);
                if ($b->end_at->greaterThanOrEqualTo($now)) {
                    $upcoming[] = $row;
                } else {
                    $history[] = $row;
                }
            }
            // История — от свежих к старым
            $history = array_reverse($history);
        }

        return view('account', [
            'phone'    => $phone,
            'client'   => $client,
            'upcoming' => $upcoming,
            'history'  => $history,
            'stats'    => [
                'count' => count($upcoming) + count($history),
                'hours' => $this->totalHours($upcoming, $history),
            ],
        ]);
    }

    private function toRow(Booking $b, string $tz): array
    {
        $tableNames = $b->bookingResources
            ->map(fn ($br) => $br->resource?->name)
            ->filter()
            ->values()
            ->all();

        $start = $b->start_at->copy()->timezone($tz);
        $end   = $b->end_at->copy()->timezone($tz);

        return [
            'day'      => $start->format('j'),
            'month'    => $this->monthShort((int) $start->format('n')),
            'table'    => $tableNames ? implode(', ', $tableNames) : 'Стол',
            'time'     => $start->format('H:i') . ' – ' . $end->format('H:i'),
            'duration' => $this->durationLabel((int) $start->diffInMinutes($end)),
            'status'   => $b->status->value,
            'status_label' => $b->status->label(),
            'amount'   => (int) round($b->amount_minor / 100),
        ];
    }

    private function totalHours(array $a, array $b): int
    {
        $sum = 0.0;
        foreach (array_merge($a, $b) as $r) {
            [$s, $e] = explode(' – ', $r['time']);
            [$sh, $sm] = array_map('intval', explode(':', $s));
            [$eh, $em] = array_map('intval', explode(':', $e));
            $sum += (($eh * 60 + $em) - ($sh * 60 + $sm)) / 60;
        }
        return (int) round($sum);
    }

    private function durationLabel(int $minutes): string
    {
        $h = $minutes / 60;
        if ($h === floor($h)) {
            return (int) $h . ' ч';
        }
        return str_replace('.', ',', (string) $h) . ' ч';
    }

    private function monthShort(int $n): string
    {
        return ['', 'янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'][$n] ?? '';
    }
}
