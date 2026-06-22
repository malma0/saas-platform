<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Booking\Actions\CancelBooking;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Services\Models\ServiceOffering;
use App\Domain\Services\Services\PricingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateBookingRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly CreateBooking $createBooking,
        private readonly CancelBooking $cancelBooking,
        private readonly PricingService $pricing,
    ) {}

    /**
     * GET /api/bookings
     * Бронирования текущего клиента (по CRM-карточке).
     */
    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $client = $this->clientFor($user);

        $bookings = Booking::query()
            // Брони клиента: созданные им самим и созданные админом для него.
            // Legacy-фолбэк по admin_id — для броней, созданных до связки с CRM.
            ->where(function ($q) use ($user, $client) {
                if ($client) {
                    $q->where('client_id', $client->id)->orWhere('admin_id', $user->id);
                } else {
                    $q->where('admin_id', $user->id);
                }
            })
            ->with(['branch', 'serviceOffering'])
            ->orderByDesc('start_at')
            ->paginate(20);

        return ApiResponse::success([
            'items' => $bookings->map(fn($b) => $this->bookingResource($b))->values(),
            'meta'  => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    /**
     * POST /api/bookings
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $user   = $request->user();
        $client = $this->clientFor($user);

        if ($client?->is_blocked) {
            return ApiResponse::error('Бронирование недоступно: обратитесь в клуб.', 403);
        }

        $service = ServiceOffering::findOrFail($request->integer('service_offering_id'));
        $branch  = Branch::findOrFail($request->integer('branch_id'));

        $startAt = Carbon::parse($request->input('start_at'))->utc();
        $endAt   = Carbon::parse($request->input('end_at'))->utc();

        // Цена считается по PricingRule в локальном времени филиала
        try {
            $price = $this->pricing->priceFor(
                $service,
                $startAt->copy()->setTimezone($branch->timezone)
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::error('Для услуги не настроена цена. ' . $e->getMessage(), 422);
        }

        $dto = new CreateBookingDTO(
            clubId:            $user->club_id,
            branchId:          $branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       $request->input('resource_ids'),
            startAt:           $startAt,
            endAt:             $endAt,
            amountMinor:       $price->amountMinor,
            currencyCode:      $price->currencyCode,
            clientId:          $client?->id,
            notes:             $request->input('notes'),
            ignoreHoldToken:   $request->input('hold_token'),
        );

        try {
            $booking = $this->createBooking->handle($dto);
        } catch (SlotNotAvailableException $e) {
            return ApiResponse::error('Слот недоступен. ' . $e->getMessage(), 422);
        }

        return ApiResponse::success(
            $this->bookingResource($booking->fresh(['branch', 'serviceOffering'])),
            'Бронирование создано.',
            201
        );
    }

    /**
     * GET /api/bookings/{id}
     */
    public function show(Request $request, string $publicId): JsonResponse
    {
        $user    = $request->user();
        $booking = Booking::where('public_id', $publicId)
            ->where('club_id', $user->club_id)
            ->with(['branch', 'serviceOffering'])
            ->firstOrFail();

        if (! $this->ownsBooking($user, $booking)) {
            return ApiResponse::error('Бронирование не найдено.', 404);
        }

        return ApiResponse::success($this->bookingResource($booking));
    }

    /**
     * DELETE /api/bookings/{id}
     * Отмена бронирования пользователем.
     */
    public function destroy(Request $request, string $publicId): JsonResponse
    {
        $user    = $request->user();
        $booking = Booking::where('public_id', $publicId)
            ->where('club_id', $user->club_id)
            ->firstOrFail();

        if (! $this->ownsBooking($user, $booking)) {
            return ApiResponse::error('Бронирование не найдено.', 404);
        }

        // Клиент может отменить только своё и только не-финальное
        if (! in_array($booking->status->value, ['pending', 'confirmed'], true)) {
            return ApiResponse::error('Бронирование нельзя отменить.', 422);
        }

        $this->cancelBooking->handle($booking, $user->id, 'Отменено клиентом');

        return ApiResponse::success(null, 'Бронирование отменено.');
    }

    // -------------------------------------------------------------------------

    /**
     * CRM-карточка текущего пользователя (создаётся при регистрации через API).
     */
    private function clientFor(User $user): ?Client
    {
        return Client::where('user_id', $user->id)->first();
    }

    /**
     * Бронь принадлежит пользователю: через его CRM-карточку
     * или (legacy) он указан создателем.
     */
    private function ownsBooking(User $user, Booking $booking): bool
    {
        if ($booking->admin_id === $user->id) {
            return true;
        }

        $client = $this->clientFor($user);

        return $client !== null && $booking->client_id === $client->id;
    }

    private function bookingResource(Booking $booking): array
    {
        return [
            'id'            => $booking->public_id,
            'branch'        => $booking->branch?->name,
            'service'       => $booking->serviceOffering?->name,
            'start_at'      => $booking->start_at?->toIso8601String(),
            'end_at'        => $booking->end_at?->toIso8601String(),
            'status'        => $booking->status?->value,
            'amount_minor'  => $booking->amount_minor,
            'currency_code' => $booking->currency_code,
            'notes'         => $booking->notes,
        ];
    }
}
