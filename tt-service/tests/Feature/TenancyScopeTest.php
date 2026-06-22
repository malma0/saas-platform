<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Money\Money;
use App\Support\Enums\BookingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 1:
 *  - BelongsToTenant скрывает данные чужого клуба
 *  - Money value-object корректно считает
 *  - BookingStatus enum корректно переходит по статусам
 */
class TenancyScopeTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Money value-object
    // -------------------------------------------------------------------------

    public function test_money_addition(): void
    {
        $a = Money::of(5000, 'RUB'); // 50 руб.
        $b = Money::of(3000, 'RUB'); // 30 руб.

        $result = $a->add($b);

        $this->assertSame(8000, $result->amountMinor);
        $this->assertSame('RUB', $result->currencyCode);
    }

    public function test_money_different_currencies_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::of(100, 'RUB')->add(Money::of(100, 'USD'));
    }

    public function test_money_from_major(): void
    {
        $money = Money::fromMajor(150.50, 'RUB');

        $this->assertSame(15050, $money->amountMinor);
    }

    // -------------------------------------------------------------------------
    // BookingStatus enum
    // -------------------------------------------------------------------------

    public function test_booking_status_valid_transition(): void
    {
        $this->assertTrue(
            BookingStatus::Pending->canTransitionTo(BookingStatus::Confirmed)
        );
        $this->assertTrue(
            BookingStatus::Confirmed->canTransitionTo(BookingStatus::Completed)
        );
    }

    public function test_booking_status_invalid_transition(): void
    {
        $this->assertFalse(
            BookingStatus::Completed->canTransitionTo(BookingStatus::Pending)
        );
        $this->assertFalse(
            BookingStatus::Cancelled->canTransitionTo(BookingStatus::Confirmed)
        );
    }

    public function test_booking_status_is_final(): void
    {
        $this->assertTrue(BookingStatus::Cancelled->isFinal());
        $this->assertTrue(BookingStatus::Completed->isFinal());
        $this->assertFalse(BookingStatus::Pending->isFinal());
        $this->assertFalse(BookingStatus::Confirmed->isFinal());
    }

    // -------------------------------------------------------------------------
    // User model: public_id генерируется автоматически
    // -------------------------------------------------------------------------

    public function test_user_gets_public_id_on_create(): void
    {
        $club = \App\Domain\ClubCore\Models\Club::factory()->create();
        $user = User::factory()->create(['club_id' => $club->id]);

        $this->assertNotEmpty($user->public_id);
        // ULID — 26 символов
        $this->assertSame(26, strlen($user->public_id));
    }

    // -------------------------------------------------------------------------
    // BelongsToTenant — заглушка до появления бизнес-моделей.
    // Реальный тест «admin клуба A не видит данные клуба B»
    // будет в Фазе 2 с моделью Club.
    // -------------------------------------------------------------------------

    public function test_superadmin_has_null_club_id(): void
    {
        $superadmin = User::factory()->create(['club_id' => null]);

        $this->assertNull($superadmin->club_id);
    }
}
