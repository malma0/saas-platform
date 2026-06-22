<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Booking;

use App\Domain\Attendance\Actions\MarkAttendance;
use App\Domain\Booking\Actions\CancelBooking;
use App\Domain\Booking\Actions\ConfirmBooking;
use App\Domain\Booking\Models\Booking;
use App\Domain\Identity\Services\AccessControlService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Domain\Payments\Actions\CreatePayment;
use App\Domain\Payments\Actions\MarkPaymentPaid;
use App\MoonShine\Resources\Booking\Pages\BookingFormPage;
use App\MoonShine\Resources\Booking\Pages\BookingIndexPage;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\PaymentStatus;
use App\Domain\Booking\Models\BookingResource as BookingResourceModel;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<Booking, BookingIndexPage, BookingFormPage, null>
 */
#[Icon('calendar-days')]
#[Group('Бронирования', 'calendar')]
#[Order(0)]
class BookingResource extends ModelResource
{
    protected string $model  = Booking::class;
    protected string $column = 'public_id';
    protected array  $with   = ['branch', 'serviceOffering', 'statusHistory', 'paidPayment', 'attendance'];
    protected bool   $simplePaginate = true;

    public function getTitle(): string { return 'Бронирования'; }


    protected function pages(): array
    {
        return [BookingIndexPage::class, BookingFormPage::class];
    }

    protected function search(): array
    {
        return ['id', 'public_id', 'notes'];
    }

    /**
     * Ограничение admin'а по филиалам (Фаза 2: user_branch_access).
     * Tenant-scope по клубу применяется в модели; здесь — фильтр филиалов.
     */
    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $allowed = app(AccessControlService::class)->accessibleBranchIds(auth()->user());

        return $allowed === null
            ? $builder
            : $builder->whereIn('branch_id', $allowed);
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        /** @var Booking $booking */
        $booking = $item->getOriginal();
        $resourceId = (int) request()->input('resource_id');

        if ($resourceId && $booking?->id) {
            BookingResourceModel::firstOrCreate(
                ['booking_id' => $booking->id, 'resource_id' => $resourceId]
            );
        }

        return $item;
    }

    // -------------------------------------------------------------------------
    // Действия над бронью: подтвердить / отменить (Фаза 12)
    // -------------------------------------------------------------------------

    #[AsyncMethod]
    public function confirm(MoonShineRequest $request, ConfirmBooking $action): JsonResponse
    {
        /** @var Booking|null $booking */
        $booking = Booking::find($request->getItemID());

        if (! $booking) {
            return JsonResponse::make()->toast('Бронь не найдена.', ToastType::ERROR);
        }

        try {
            $action->handle($booking, auth()->id(), 'Подтверждено через админку');
        } catch (\Throwable $e) {
            return JsonResponse::make()->toast($e->getMessage(), ToastType::ERROR);
        }

        return JsonResponse::make()
            ->toast('Бронь подтверждена.', ToastType::SUCCESS)
            ->events([$this->getListEventName()]);
    }

    #[AsyncMethod]
    public function cancelBooking(MoonShineRequest $request, CancelBooking $action): JsonResponse
    {
        /** @var Booking|null $booking */
        $booking = Booking::find($request->getItemID());

        if (! $booking) {
            return JsonResponse::make()->toast('Бронь не найдена.', ToastType::ERROR);
        }

        try {
            $action->handle($booking, auth()->id(), 'Отменено через админку');
        } catch (\Throwable $e) {
            return JsonResponse::make()->toast($e->getMessage(), ToastType::ERROR);
        }

        return JsonResponse::make()
            ->toast('Бронь отменена.', ToastType::SUCCESS)
            ->events([$this->getListEventName()]);
    }

    // -------------------------------------------------------------------------
    // Посещаемость (Фаза 9 / ТЗ Блок 6)
    // -------------------------------------------------------------------------

    #[AsyncMethod]
    public function markPresent(MoonShineRequest $request, MarkAttendance $action): JsonResponse
    {
        return $this->markAttendance($request, $action, AttendanceStatus::Present, 'Отмечен приход.');
    }

    #[AsyncMethod]
    public function markNoShow(MoonShineRequest $request, MarkAttendance $action): JsonResponse
    {
        return $this->markAttendance($request, $action, AttendanceStatus::NoShow, 'Отмечена неявка.');
    }

    private function markAttendance(MoonShineRequest $request, MarkAttendance $action, AttendanceStatus $status, string $okMsg): JsonResponse
    {
        /** @var Booking|null $booking */
        $booking = Booking::find($request->getItemID());
        if (! $booking) {
            return JsonResponse::make()->toast('Бронь не найдена.', ToastType::ERROR);
        }

        try {
            $action->handle($booking, $status, auth()->id(), 'Отмечено через админку');
        } catch (\Throwable $e) {
            return JsonResponse::make()->toast($e->getMessage(), ToastType::ERROR);
        }

        return JsonResponse::make()
            ->toast($okMsg, ToastType::SUCCESS)
            ->events([$this->getListEventName()]);
    }

    // -------------------------------------------------------------------------
    // Оплата (Фаза 10 — ручная отметка)
    // -------------------------------------------------------------------------

    #[AsyncMethod]
    public function markPaid(MoonShineRequest $request, CreatePayment $create, MarkPaymentPaid $markPaid): JsonResponse
    {
        /** @var Booking|null $booking */
        $booking = Booking::with('payments')->find($request->getItemID());
        if (! $booking) {
            return JsonResponse::make()->toast('Бронь не найдена.', ToastType::ERROR);
        }

        try {
            // Берём незавершённый платёж или создаём новый на сумму брони
            $payment = $booking->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->first();

            if (! $payment) {
                $payment = $create->handle($booking, 'manual', auth()->id());
            }

            $markPaid->handle($payment);
        } catch (\Throwable $e) {
            return JsonResponse::make()->toast($e->getMessage(), ToastType::ERROR);
        }

        return JsonResponse::make()
            ->toast('Оплата отмечена.', ToastType::SUCCESS)
            ->events([$this->getListEventName()]);
    }
}
