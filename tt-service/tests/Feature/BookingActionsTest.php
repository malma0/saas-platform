<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Booking\Actions\ConfirmBooking;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Models\Booking;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Действия над бронью: подтверждение (ConfirmBooking) — для админ-кнопки «Подтвердить».
 */
class BookingActionsTest extends TestCase
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

    public function test_confirm_moves_pending_to_confirmed(): void
    {
        $booking = $this->makeBooking();
        $booking->update(['status' => BookingStatus::Pending]);

        app(ConfirmBooking::class)->handle($booking->fresh(), confirmedBy: null);

        $this->assertSame('confirmed', $booking->fresh()->status->value);
        $this->assertDatabaseHas('booking_status_history', [
            'booking_id' => $booking->id,
            'status'     => 'confirmed',
        ]);
    }

    public function test_confirm_is_idempotent_for_already_confirmed(): void
    {
        $booking = $this->makeBooking(); // создаётся сразу confirmed

        $result = app(ConfirmBooking::class)->handle($booking);

        $this->assertSame('confirmed', $result->status->value);
    }

    public function test_confirm_rejects_final_booking(): void
    {
        $booking = $this->makeBooking();
        $booking->update(['status' => BookingStatus::Cancelled]);

        $this->expectException(\RuntimeException::class);
        app(ConfirmBooking::class)->handle($booking->fresh());
    }

    // -------------------------------------------------------------------------

    private function makeBooking(): Booking
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
