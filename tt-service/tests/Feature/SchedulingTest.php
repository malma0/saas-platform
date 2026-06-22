<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Scheduling\Actions\CancelSession;
use App\Domain\Scheduling\Actions\CancelTemplateSeries;
use App\Domain\Scheduling\Actions\CreateScheduleTemplate;
use App\Domain\Scheduling\DTO\CreateScheduleTemplateDTO;
use App\Domain\Scheduling\Models\ScheduleTemplate;
use App\Domain\Scheduling\Models\ServiceSession;
use App\Domain\Scheduling\Services\SessionMaterializer;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Enums\SessionStatus;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 7 — Scheduling / Recurring.
 *
 * ✅ Готово, если: создание шаблона генерирует занятия и резервирует ресурсы;
 *                 отмена одного занятия и всей серии работает.
 */
class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    private Club            $club;
    private Branch          $branch;
    private ResourceType    $typeTable;
    private Resource        $table1;
    private ServiceOffering $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club   = Club::factory()->create();

        $this->branch = (new \App\Domain\Facilities\Actions\CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Тест', timezone: 'Europe/Moscow')
        );

        $this->typeTable = ResourceType::create([
            'club_id' => $this->club->id,
            'slug'    => 'table',
            'name'    => 'Стол',
        ]);

        $this->table1 = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $this->typeTable->id, name: 'Стол 1'
        ));

        $this->service = ServiceOffering::factory()->create([
            'club_id'          => $this->club->id,
            'duration_minutes' => 60,
        ]);
        PricingRule::create([
            'service_offering_id' => $this->service->id,
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTemplate(
        string $rrule = 'FREQ=WEEKLY;BYDAY=MO',
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        string $startTime = '10:00',
    ): CreateScheduleTemplateDTO {
        return new CreateScheduleTemplateDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $this->service->id,
            recurrenceRule:    $rrule,
            startDate:         $startDate ?? Carbon::today('Europe/Moscow'),
            endDate:           $endDate,
            startTime:         $startTime,
            durationMinutes:   60,
            resourceIds:       [$this->table1->id],
        );
    }

    // -------------------------------------------------------------------------
    // Тест 1: создание шаблона генерирует занятия
    // -------------------------------------------------------------------------

    public function test_create_template_generates_sessions(): void
    {
        $dto = $this->makeTemplate('FREQ=WEEKLY;BYDAY=MO,WE,FR');

        $template = (new CreateScheduleTemplate())->handle($dto);

        $this->assertInstanceOf(ScheduleTemplate::class, $template);
        $this->assertNotEmpty($template->public_id);

        // Должны быть занятия на горизонт 90 дней
        $sessionCount = $template->sessions()->count();
        $this->assertGreaterThan(0, $sessionCount);

        // За 90 дней MO+WE+FR ≈ 13 недель × 3 = ~39 занятий
        $this->assertGreaterThanOrEqual(30, $sessionCount);
    }

    // -------------------------------------------------------------------------
    // Тест 2: сессия резервирует ресурс (виден в AvailabilityService)
    // -------------------------------------------------------------------------

    public function test_session_reserves_resource(): void
    {
        $dto = $this->makeTemplate('FREQ=WEEKLY;BYDAY=MO');
        $template = (new CreateScheduleTemplate())->handle($dto);

        $firstSession = $template->sessions()
            ->where('is_cancelled', false)
            ->orderBy('start_at')
            ->first();

        $this->assertNotNull($firstSession, 'Нет сессий в шаблоне');

        // Сессия должна иметь ресурс
        $resourceIds = $firstSession->resources()->pluck('resources.id')->toArray();
        $this->assertContains($this->table1->id, $resourceIds);
    }

    // -------------------------------------------------------------------------
    // Тест 3: регулярное занятие блокирует обычную бронь
    // -------------------------------------------------------------------------

    public function test_session_blocks_booking(): void
    {
        $dto = $this->makeTemplate('FREQ=WEEKLY;BYDAY=MO');
        $template = (new CreateScheduleTemplate())->handle($dto);

        $firstSession = $template->sessions()
            ->where('is_cancelled', false)
            ->orderBy('start_at')
            ->first();

        $this->assertNotNull($firstSession);

        // Попытка забронировать тот же ресурс на то же время
        $this->expectException(SlotNotAvailableException::class);
        (new CreateBooking())->handle(new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $this->service->id,
            resourceIds:       [$this->table1->id],
            startAt:           $firstSession->start_at,
            endAt:             $firstSession->end_at,
            amountMinor:       50000,
        ));
    }

    // -------------------------------------------------------------------------
    // Тест 4: материализация идемпотентна (нет дубликатов)
    // -------------------------------------------------------------------------

    public function test_materialize_is_idempotent(): void
    {
        $dto      = $this->makeTemplate('FREQ=WEEKLY;BYDAY=TU');
        $template = (new CreateScheduleTemplate())->handle($dto);

        $countFirst = $template->sessions()->count();

        // Повторный вызов
        $materializer = new SessionMaterializer();
        $new = $materializer->materialize($template, [$this->table1->id]);

        $this->assertEmpty($new, 'Повторная материализация не должна создавать дубли');
        $this->assertEquals($countFirst, $template->sessions()->count());
    }

    // -------------------------------------------------------------------------
    // Тест 5: отмена одного занятия
    // -------------------------------------------------------------------------

    public function test_cancel_single_session(): void
    {
        $dto      = $this->makeTemplate('FREQ=WEEKLY;BYDAY=WE');
        $template = (new CreateScheduleTemplate())->handle($dto);

        $session = $template->sessions()
            ->where('is_cancelled', false)
            ->orderBy('start_at')
            ->first();

        $this->assertNotNull($session);

        // Отменяем одно занятие
        $cancelled = (new CancelSession())->handle($session, 'Тренер заболел');

        $this->assertTrue($cancelled->is_cancelled);
        $this->assertEquals(SessionStatus::Cancelled, $cancelled->status);

        // Ресурс должен быть освобождён
        $this->assertEmpty($cancelled->resources()->get());

        // Исключение записано
        $this->assertDatabaseHas('schedule_exceptions', [
            'schedule_template_id' => $template->id,
            'type'                 => 'cancelled',
        ]);
    }

    // -------------------------------------------------------------------------
    // Тест 6: после отмены занятия слот становится доступным
    // -------------------------------------------------------------------------

    public function test_cancelled_session_frees_slot_for_booking(): void
    {
        $dto      = $this->makeTemplate('FREQ=WEEKLY;BYDAY=TH');
        $template = (new CreateScheduleTemplate())->handle($dto);

        $session = $template->sessions()
            ->where('is_cancelled', false)
            ->orderBy('start_at')
            ->first();

        // Отменяем
        (new CancelSession())->handle($session);

        // Теперь должна пройти обычная бронь на то же время
        $booking = (new CreateBooking())->handle(new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $this->service->id,
            resourceIds:       [$this->table1->id],
            startAt:           $session->start_at,
            endAt:             $session->end_at,
            amountMinor:       50000,
        ));

        $this->assertNotNull($booking->id);
    }

    // -------------------------------------------------------------------------
    // Тест 7: отмена нельзя применить дважды
    // -------------------------------------------------------------------------

    public function test_cannot_cancel_session_twice(): void
    {
        $dto      = $this->makeTemplate('FREQ=WEEKLY;BYDAY=FR');
        $template = (new CreateScheduleTemplate())->handle($dto);

        $session = $template->sessions()->orderBy('start_at')->first();

        (new CancelSession())->handle($session);

        $this->expectException(\RuntimeException::class);
        (new CancelSession())->handle($session->fresh());
    }

    // -------------------------------------------------------------------------
    // Тест 8: отмена всей серии
    // -------------------------------------------------------------------------

    public function test_cancel_series_removes_future_sessions(): void
    {
        $dto = $this->makeTemplate('FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR');
        $template = (new CreateScheduleTemplate())->handle($dto);

        $totalBefore = $template->sessions()->where('is_cancelled', false)->count();
        $this->assertGreaterThan(0, $totalBefore);

        // Отменяем серию с завтра
        $cancelFrom = Carbon::tomorrow('UTC');
        (new CancelTemplateSeries())->handle($template, $cancelFrom);

        $template->refresh();

        // end_date выставлен
        $this->assertNotNull($template->end_date);

        // Все будущие сессии (с cancelFrom) должны быть cancelled+deleted
        $remaining = ServiceSession::withoutGlobalScopes()
            ->where('schedule_template_id', $template->id)
            ->where('start_at', '>=', $cancelFrom->utc())
            ->whereNull('deleted_at')
            ->count();

        $this->assertEquals(0, $remaining);
    }

    // -------------------------------------------------------------------------
    // Тест 9: материализация сессий инвалидирует кэш доступности (Фаза 14)
    // -------------------------------------------------------------------------

    public function test_session_materialization_invalidates_cache(): void
    {
        $spy = $this->spy(\App\Domain\Booking\Cache\AvailabilityCache::class);

        (new CreateScheduleTemplate())->handle($this->makeTemplate('FREQ=WEEKLY;BYDAY=MO'));

        // Создание резерва должно сбросить кэш филиала
        $spy->shouldHaveReceived('invalidateBranch')->with($this->branch->id);
    }

    // -------------------------------------------------------------------------
    // Тест 10: отмена занятия инвалидирует кэш доступности (Фаза 14)
    // -------------------------------------------------------------------------

    public function test_cancel_session_invalidates_cache(): void
    {
        $template = (new CreateScheduleTemplate())->handle($this->makeTemplate('FREQ=WEEKLY;BYDAY=MO'));
        $session  = $template->sessions()->orderBy('start_at')->first();

        $spy = $this->spy(\App\Domain\Booking\Cache\AvailabilityCache::class);

        (new CancelSession())->handle($session);

        $spy->shouldHaveReceived('invalidateBranch')->with($this->branch->id);
    }
}
