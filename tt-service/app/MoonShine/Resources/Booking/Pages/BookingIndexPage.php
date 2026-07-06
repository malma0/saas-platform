<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Booking\Pages;

use App\Domain\Booking\Models\Booking;
use App\Support\Enums\BookingStatus;
use App\MoonShine\Resources\Booking\BookingResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<BookingResource> */
final class BookingIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Публичный ID', 'public_id'),
            Text::make('Филиал', 'branch.name'),
            Text::make('Услуга', 'serviceOffering.name'),
            Date::make('Начало', 'start_at')->sortable()
                ->changePreview($this->inBranchTz('start_at')),
            Date::make('Конец', 'end_at')
                ->changePreview($this->inBranchTz('end_at')),
            Enum::make('Статус', 'status')->attach(BookingStatus::class)->sortable(),
            Text::make('Примечание', 'notes'),
        ];
    }

    /**
     * Колбэк отображения даты в часовом поясе филиала брони (а не в UTC).
     * Так время в админке совпадает с тем, что видит клиент на сайте.
     */
    private function inBranchTz(string $attr): \Closure
    {
        return function ($value, $field) use ($attr) {
            $booking = $field->getData()?->getOriginal();
            $tz      = $booking?->branch?->timezone ?? config('app.timezone', 'UTC');
            $dt      = $booking?->{$attr};

            return $dt instanceof \Carbon\CarbonInterface
                ? $dt->copy()->timezone($tz)->format('d.m.Y H:i')
                : (string) $value;
        };
    }

    protected function filters(): iterable
    {
        return [
            Enum::make('Статус', 'status')->attach(BookingStatus::class)->nullable(),
            Date::make('Начало от', 'start_at')->withTime(),
        ];
    }

    /**
     * Кнопки строки: подтвердить, перенести, оплата, посещение, отменить.
     */
    protected function buttons(): ListOf
    {
        return parent::buttons()->prepend(
            ActionButton::make('Подтвердить')
                ->icon('check')
                ->method('confirm')
                ->showInDropdown()
                ->canSee(fn (?Booking $b) => $b !== null && $b->isPending()),

            ActionButton::make('Перенести')
                ->icon('arrows-right-left')
                ->inModal(
                    title: static fn () => 'Перенос брони',
                    content: fn (mixed $item) => $this->rescheduleForm($item),
                )
                ->showInDropdown()
                ->canSee(fn (?Booking $b) => $b !== null && ! $b->isFinal()),

            // Оплата — пока бронь не отменена и ещё не оплачена
            ActionButton::make('Оплата')
                ->icon('banknotes')
                ->method('markPaid')
                ->showInDropdown()
                ->canSee(fn (?Booking $b) => $b !== null && ! $b->isCancelled() && $b->paidPayment === null)
                ->success(),

            // Посещение — для подтверждённых/завершённых
            ActionButton::make('Пришёл')
                ->icon('user-plus')
                ->method('markPresent')
                ->showInDropdown()
                ->canSee(fn (?Booking $b) => $b !== null && ($b->isConfirmed() || $b->isCompleted()))
                ->success(),

            ActionButton::make('Не пришёл')
                ->icon('user-minus')
                ->method('markNoShow')
                ->showInDropdown()
                ->canSee(fn (?Booking $b) => $b !== null && ($b->isConfirmed() || $b->isCompleted())),

            ActionButton::make('Отменить')
                ->icon('x-mark')
                ->method('cancelBooking')
                ->showInDropdown()
                ->canSee(fn (?Booking $b) => $b !== null && ! $b->isFinal())
                ->error(),
        );
    }

    /**
     * HTML-форма переноса (datetime-local в таймзоне филиала),
     * POST на защищённый web-маршрут admin.booking.reschedule.
     */
    private function rescheduleForm(mixed $item): string
    {
        $booking = $item instanceof Booking ? $item : Booking::find(data_get($item, 'id'));
        if (! $booking) {
            return '<p>Бронь не найдена.</p>';
        }

        $tz    = $booking->branch?->timezone ?? config('app.timezone', 'UTC');
        $start = $booking->start_at?->copy()->setTimezone($tz)->format('Y-m-d\TH:i');
        $end   = $booking->end_at?->copy()->setTimezone($tz)->format('Y-m-d\TH:i');
        $url   = route('admin.booking.reschedule', $booking->public_id);
        $csrf  = csrf_token();

        return <<<HTML
<form method="POST" action="{$url}" style="display:flex;flex-direction:column;gap:12px;">
    <input type="hidden" name="_token" value="{$csrf}">
    <label style="display:flex;flex-direction:column;gap:4px;">
        <span>Новое начало ({$tz})</span>
        <input type="datetime-local" name="new_start" value="{$start}" required
               style="padding:6px;border:1px solid #ccc;border-radius:6px;">
    </label>
    <label style="display:flex;flex-direction:column;gap:4px;">
        <span>Новый конец ({$tz})</span>
        <input type="datetime-local" name="new_end" value="{$end}" required
               style="padding:6px;border:1px solid #ccc;border-radius:6px;">
    </label>
    <button type="submit"
            style="padding:8px 14px;background:#7c3aed;color:#fff;border:none;border-radius:6px;cursor:pointer;">
        Перенести
    </button>
</form>
HTML;
    }
}
