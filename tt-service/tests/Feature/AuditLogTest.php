<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Booking\Actions\CancelBooking;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Models\ServiceOffering;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Правило 5 (НЕНАРУШАЕМОЕ) — аудит изменений.
 *
 * Проверяем, что spatie/laravel-activitylog фиксирует изменения
 * брони, цены, статуса, отмены и карточки клиента.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private Club            $club;
    private Branch          $branch;
    private ServiceOffering $service;
    private int             $resourceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club   = Club::factory()->create();
        $this->branch = Branch::factory()->create(['club_id' => $this->club->id]);
        $resType      = ResourceType::factory()->create(['club_id' => $this->club->id]);
        $this->resourceId = app(CreateResource::class)->handle(new CreateResourceDTO(
            clubId:         $this->club->id,
            branchId:       $this->branch->id,
            resourceTypeId: $resType->id,
            name:           'Стол 1',
        ))->id;
        $this->service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);
    }

    public function test_booking_creation_is_logged(): void
    {
        $booking = $this->makeBooking();

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'booking',
            'subject_type'=> $booking::class,
            'subject_id'  => $booking->id,
            'event'       => 'created',
        ]);
    }

    public function test_booking_cancellation_is_logged(): void
    {
        $booking = $this->makeBooking();
        app(CancelBooking::class)->handle($booking);

        // Должна быть запись об обновлении статуса
        $logged = Activity::where('log_name', 'booking')
            ->where('subject_id', $booking->id)
            ->where('event', 'updated')
            ->exists();

        $this->assertTrue($logged, 'Отмена брони должна писаться в activity_log');
    }

    public function test_client_changes_are_logged(): void
    {
        $client = Client::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Иван']);
        $client->update(['first_name' => 'Пётр']);

        $this->assertDatabaseHas('activity_log', [
            'log_name'   => 'client',
            'subject_id' => $client->id,
            'event'      => 'updated',
        ]);

        // Проверяем, что в свойствах зафиксировано изменение имени
        $activity = Activity::where('log_name', 'client')
            ->where('subject_id', $client->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Пётр', $activity->changes()['attributes']['first_name'] ?? null);
        $this->assertSame('Иван', $activity->changes()['old']['first_name'] ?? null);
    }

    public function test_only_dirty_attributes_logged(): void
    {
        $client = Client::factory()->create(['club_id' => $this->club->id]);
        $countBefore = Activity::where('subject_id', $client->id)->count();

        // Сохранение без изменений — не должно плодить записи (dontSubmitEmptyLogs)
        $client->save();

        $this->assertSame($countBefore, Activity::where('subject_id', $client->id)->count());
    }

    // -------------------------------------------------------------------------

    private function makeBooking()
    {
        return app(CreateBooking::class)->handle(new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $this->service->id,
            resourceIds:       [$this->resourceId],
            startAt:           Carbon::now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0)->utc(),
            endAt:             Carbon::now()->addDays(2)->setHour(11)->setMinute(0)->setSecond(0)->utc(),
            amountMinor:       50000,
        ));
    }
}
