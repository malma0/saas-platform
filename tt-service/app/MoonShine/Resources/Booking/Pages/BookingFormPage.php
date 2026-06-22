<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Booking\Pages;

use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Booking\Services\ConflictChecker;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Support\Enums\BookingStatus;
use App\MoonShine\Resources\Booking\BookingResource;
use App\MoonShine\Resources\Branch\BranchResource;
use App\MoonShine\Resources\ServiceOffering\ServiceOfferingResource;
use Carbon\Carbon;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/** @extends FormPage<BookingResource> */
final class BookingFormPage extends FormPage
{
    protected function fields(): iterable
    {
        $item  = $this->getResource()->getItem();
        $isNew = is_null($item?->getKey());

        // Столы. Префикс филиала показываем только если филиалов больше одного.
        $multiBranch = Branch::query()->count() > 1;

        $tableOptions = Resource::with('branch')
            ->where('resource_type_id', 1)
            ->where('is_active', true)
            ->orderBy('branch_id')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn($r) => [
                $r->id => $multiBranch ? ($r->branch?->name . ' → ' . $r->name) : $r->name,
            ])
            ->all();

        return [
            Box::make('Основное', [
                ID::make(),
                Text::make('Публичный ID', 'public_id')->readonly(),
                Flex::make([
                    BelongsTo::make('Филиал', 'branch', formatted: fn($m) => $m->name, resource: BranchResource::class),
                    BelongsTo::make('Услуга', 'serviceOffering', formatted: fn($m) => $m->name, resource: ServiceOfferingResource::class),
                ]),
                Flex::make([
                    Date::make('Начало', 'start_at')->withTime()->when(
                        ! $isNew,
                        fn($f) => $f->readonly()
                    ),
                    Date::make('Конец', 'end_at')->withTime()->when(
                        ! $isNew,
                        fn($f) => $f->readonly()
                    ),
                ]),
                Select::make('Стол', 'resource_id')
                    ->options($tableOptions)
                    // Виртуальное поле: НЕ пишем в таблицу bookings (там нет resource_id).
                    // Связь со столом создаётся в BookingResource::afterCreated().
                    ->onApply(static fn($item) => $item)
                    ->when($isNew, fn($f) => $f->required())
                    ->when(! $isNew, fn($f) => $f->readonly()),
                Enum::make('Статус', 'status')->attach(BookingStatus::class)->required(),
                Number::make('Сумма (копейки)', 'amount_minor')->min(0),
                Textarea::make('Примечание', 'notes'),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        $isNew = is_null($item->getOriginal()?->getKey());

        $resourceRules = $isNew
            ? ['required', 'integer', 'exists:resources,id']
            : ['nullable'];

        // Бизнес-проверки слота (только при создании): один день, рабочие часы,
        // принадлежность стола филиалу, отсутствие пересечений с другими бронями.
        if ($isNew) {
            $resourceRules[] = function (string $attribute, mixed $value, \Closure $fail): void {
                $error = $this->slotError(
                    (int) request('branch_id'),
                    (int) $value,
                    request('start_at'),
                    request('end_at'),
                );

                if ($error !== null) {
                    $fail($error);
                }
            };
        }

        return [
            'branch_id'           => 'required|integer|exists:branches,id',
            'service_offering_id' => 'required|integer|exists:service_offerings,id',
            'start_at'            => 'required|date',
            'end_at'              => 'required|date|after:start_at',
            'status'              => 'required|in:pending,confirmed,cancelled,completed,no_show',
            'resource_id'         => $resourceRules,
        ];
    }

    /**
     * Проверка корректности слота. Возвращает текст ошибки или null, если всё ок.
     * Время трактуем в часовом поясе филиала (open_time/close_time — локальные).
     */
    private function slotError(?int $branchId, ?int $resourceId, ?string $start, ?string $end): ?string
    {
        // Недостающие поля отловят базовые required-правила.
        if (! $branchId || ! $resourceId || ! $start || ! $end) {
            return null;
        }

        $branch = Branch::with('workingHours')->find($branchId);
        if (! $branch) {
            return null;
        }

        $tz = $branch->timezone ?: config('app.timezone');

        try {
            $s = Carbon::parse($start, $tz);
            $e = Carbon::parse($end, $tz);
        } catch (\Throwable) {
            return 'Некорректные дата или время.';
        }

        if ($e->lessThanOrEqualTo($s)) {
            return 'Конец брони должен быть позже начала.';
        }

        // 1) Бронь в пределах одних суток
        if (! $s->isSameDay($e)) {
            return 'Бронь должна укладываться в один день — нельзя переносить на следующие сутки.';
        }

        // 2) Рабочие часы филиала
        $wh = $branch->getWorkingHourForDay($s->dayOfWeek);
        if (! $wh || $wh->is_closed) {
            return 'В этот день филиал не работает (выходной).';
        }

        $open  = $s->copy()->setTimeFromTimeString((string) $wh->open_time);
        $close = $s->copy()->setTimeFromTimeString((string) $wh->close_time);

        if ($s->lessThan($open) || $e->greaterThan($close)) {
            return sprintf(
                'Время вне часов работы: можно бронировать только с %s до %s.',
                substr((string) $wh->open_time, 0, 5),
                substr((string) $wh->close_time, 0, 5),
            );
        }

        // 3) Стол должен относиться к выбранному филиалу
        $resource = Resource::find($resourceId);
        if (! $resource || (int) $resource->branch_id !== $branchId) {
            return 'Выбранный стол не относится к этому филиалу.';
        }

        // 4) Нет пересечений с другими бронями / занятиями / резервами
        try {
            app(ConflictChecker::class)->assertNoConflict(
                $resource->relatedResourceIds(),
                $s->format('Y-m-d H:i:s'),
                $e->format('Y-m-d H:i:s'),
                $branchId,
            );
        } catch (SlotNotAvailableException $ex) {
            return $ex->getMessage();
        }

        return null;
    }
}
