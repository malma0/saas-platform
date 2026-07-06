<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Services\Models\ServiceOffering;
use App\Domain\Services\Services\PricingService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Гостевое бронирование с публичного сайта.
 *
 * Замыкает петлю «сайт → БД → MoonShine»: посетитель без входа оставляет имя
 * и телефон, бронь создаётся тем же доменным движком (CreateBooking), что и в
 * админке, и сразу видна в MoonShine и в расписании.
 *
 * Гость не авторизован → tenant-scope не применяется, поэтому club_id всюду
 * проставляем явно (из филиала).
 *
 * Время принимаем локальное (date + start + end в часовом поясе филиала) —
 * перевод в UTC делаем на сервере, чтобы на клиенте не было ошибок пояса.
 */
class BookingController extends Controller
{
    /**
     * На сколько дней вперёд клиент может бронировать онлайн.
     * Дальше — только через администратора (там, где уже расставлены
     * регулярные мероприятия). Админ в сетке бронирует без ограничения.
     */
    private const MAX_DAYS_AHEAD = 7;

    /** Телефон администратора для подсказки при брони на дальний срок. */
    private const ADMIN_PHONE = '+7 (383) 207-86-20';

    public function __construct(
        private readonly CreateBooking $createBooking,
        private readonly PricingService $pricing,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|min:2|max:120',
            'phone'               => 'required|string|min:5|max:30',
            'branch_id'           => 'required|integer|exists:branches,id',
            'service_offering_id' => 'required|integer|exists:service_offerings,id',
            'resource_id'         => 'required|integer|exists:resources,id',
            'date'                => 'required|date_format:Y-m-d',
            'start'               => 'required|date_format:H:i',
            'end'                 => 'required|date_format:H:i',
        ]);

        $branch  = Branch::findOrFail($data['branch_id']);
        $service = ServiceOffering::findOrFail($data['service_offering_id']);
        $tz      = $branch->timezone ?: 'Europe/Moscow';

        // Локальное время филиала → UTC
        $startLocal = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['start']}", $tz);
        $endLocal   = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['end']}", $tz);

        if ($endLocal->lessThanOrEqualTo($startLocal)) {
            return response()->json(['ok' => false, 'message' => 'Время окончания должно быть позже начала.'], 422);
        }

        if ($startLocal->isPast()) {
            return response()->json(['ok' => false, 'message' => 'Нельзя забронировать прошедшее время.'], 422);
        }

        // Онлайн — только на ближайшую неделю; дальше расписание ещё не сформировано.
        $maxDate = Carbon::now($tz)->startOfDay()->addDays(self::MAX_DAYS_AHEAD);
        if ($startLocal->copy()->startOfDay()->greaterThan($maxDate)) {
            return response()->json([
                'ok'            => false,
                'contact_admin' => true,
                'message'       => 'Онлайн-бронирование доступно только на неделю вперёд. '
                    . 'Чтобы забронировать на более поздний срок, свяжитесь с администратором: ' . self::ADMIN_PHONE . '.',
            ], 422);
        }

        // Проверка рабочих часов филиала на этот день недели
        $wh = $branch->getWorkingHourForDay((int) $startLocal->dayOfWeek);
        if (! $wh || $wh->is_closed) {
            return response()->json(['ok' => false, 'message' => 'В этот день клуб не работает.'], 422);
        }
        $openAt  = $startLocal->copy()->setTimeFromTimeString($wh->open_time);
        $closeAt = $startLocal->copy()->setTimeFromTimeString($wh->close_time);
        if ($startLocal->lt($openAt) || $endLocal->gt($closeAt)) {
            return response()->json([
                'ok'      => false,
                'message' => "Время вне рабочих часов клуба ({$wh->open_time}–{$wh->close_time}).",
            ], 422);
        }

        // Цена по PricingRule (трактуем правило как ставку за час) × длительность
        try {
            $hourly = $this->pricing->priceFor($service, $startLocal);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => 'Для услуги не настроена цена.'], 422);
        }
        $hours       = $startLocal->diffInMinutes($endLocal) / 60;
        $amountMinor = (int) round($hourly->amountMinor * $hours);

        $client = $this->findOrCreateClient($branch->club_id, $data['name'], $data['phone']);

        $dto = new CreateBookingDTO(
            clubId:            $branch->club_id,
            branchId:          $branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       [(int) $data['resource_id']],
            startAt:           $startLocal->copy()->utc(),
            endAt:             $endLocal->copy()->utc(),
            amountMinor:       $amountMinor,
            currencyCode:      $hourly->currencyCode,
            clientId:          $client->id,
            adminId:           null,
            notes:             'Бронь с сайта',
        );

        try {
            $booking = $this->createBooking->handle($dto);
        } catch (SlotNotAvailableException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'ok'         => true,
            'message'    => 'Стол забронирован.',
            'public_id'  => $booking->public_id,
            'start'      => $data['start'],
            'end'        => $data['end'],
            'amount'     => $amountMinor / 100,
            'account_url' => route('web.account', ['phone' => $data['phone']]),
        ], 201);
    }

    /**
     * Найти клиента по телефону в рамках клуба или создать нового.
     */
    private function findOrCreateClient(int $clubId, string $name, string $phone): Client
    {
        $phone = trim($phone);

        $client = Client::withoutGlobalScopes()
            ->where('club_id', $clubId)
            ->where('phone', $phone)
            ->first();

        if ($client) {
            return $client;
        }

        $parts     = preg_split('/\s+/', trim($name), 2);
        $firstName = $parts[0] ?? $name;
        $lastName  = $parts[1] ?? null;

        return Client::create([
            'club_id'    => $clubId,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone,
            'source'     => 'website',
        ]);
    }
}
