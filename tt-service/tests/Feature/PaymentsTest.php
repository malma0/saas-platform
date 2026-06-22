<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Models\Booking;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Payments\Actions\CreatePayment;
use App\Domain\Payments\Actions\MarkPaymentPaid;
use App\Domain\Payments\Actions\RefundPayment;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\ProviderWebhookEvent;
use App\Domain\Payments\Services\WebhookProcessor;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Enums\PaymentStatus;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 10 — Payments.
 *
 * ✅ Готово, если: бронь можно связать с платежом, статусы переключаются,
 *                  структура готова под провайдера.
 */
class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    private Club            $club;
    private Branch          $branch;
    private Resource        $table;
    private ServiceOffering $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club   = Club::factory()->create();
        $this->branch = (new \App\Domain\Facilities\Actions\CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Тест', timezone: 'Europe/Moscow')
        );

        $type = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'table', 'name' => 'Стол']);
        $this->table = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $type->id, name: 'Стол 1'
        ));

        $this->service = ServiceOffering::factory()->create([
            'club_id' => $this->club->id, 'duration_minutes' => 60,
        ]);
        PricingRule::create([
            'service_offering_id' => $this->service->id,
            'amount_minor' => 50000, 'currency_code' => 'RUB', 'priority' => 0,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function makeBooking(?Carbon $startAt = null): Booking
    {
        $start = $startAt ?? Carbon::tomorrow()->setTime(10, 0)->utc();
        return (new CreateBooking())->handle(new CreateBookingDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            serviceOfferingId: $this->service->id, resourceIds: [$this->table->id],
            startAt: $start, endAt: $start->copy()->addHour(),
            amountMinor: 50000,
        ));
    }

    // -------------------------------------------------------------------------
    // Создание и оплата
    // -------------------------------------------------------------------------

    public function test_create_payment_for_booking(): void
    {
        $booking = $this->makeBooking();
        $payment = (new CreatePayment())->handle($booking, provider: 'manual');

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertNotEmpty($payment->public_id);
        $this->assertEquals(PaymentStatus::Pending, $payment->status);
        $this->assertEquals(50000, $payment->amount_minor);
        $this->assertEquals($booking->id, $payment->booking_id);
    }

    public function test_payment_has_ulid_public_id(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(11, 0)->utc());
        $payment = (new CreatePayment())->handle($booking);

        $this->assertMatchesRegularExpression('/^[0-9a-z]{26}$/', $payment->public_id);
    }

    public function test_mark_payment_paid(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(12, 0)->utc());
        $payment = (new CreatePayment())->handle($booking);

        $paid = (new MarkPaymentPaid())->handle($payment);

        $this->assertEquals(PaymentStatus::Paid, $paid->status);

        // Транзакция записана
        $this->assertDatabaseHas('payment_transactions', [
            'payment_id' => $payment->id,
            'type'       => 'charge',
            'amount_minor' => 50000,
        ]);
    }

    public function test_booking_has_payments_relation(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(13, 0)->utc());
        (new CreatePayment())->handle($booking);

        $this->assertCount(1, $booking->fresh()->payments);
    }

    public function test_cannot_pay_twice(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(14, 0)->utc());
        $payment = (new CreatePayment())->handle($booking);
        (new MarkPaymentPaid())->handle($payment);

        // Попытка создать второй платёж для уже оплаченной брони
        $this->expectException(\RuntimeException::class);
        (new CreatePayment())->handle($booking->fresh());
    }

    public function test_cannot_mark_already_paid_payment(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(15, 0)->utc());
        $payment = (new CreatePayment())->handle($booking);
        (new MarkPaymentPaid())->handle($payment);

        $this->expectException(\RuntimeException::class);
        (new MarkPaymentPaid())->handle($payment->fresh());
    }

    // -------------------------------------------------------------------------
    // Возврат
    // -------------------------------------------------------------------------

    public function test_full_refund(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(9, 0)->utc());
        $payment = (new CreatePayment())->handle($booking);
        (new MarkPaymentPaid())->handle($payment);

        $refund = (new RefundPayment())->handle($payment->fresh(), 50000, 'Клиент отменил');

        $this->assertEquals('completed', $refund->status);
        $this->assertEquals(50000, $refund->amount_minor);

        // Платёж перешёл в Refunded
        $this->assertEquals(PaymentStatus::Refunded, $payment->fresh()->status);
    }

    public function test_partial_refund(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(8, 0)->utc());
        $payment = (new CreatePayment())->handle($booking);
        (new MarkPaymentPaid())->handle($payment);

        // Частичный возврат — половина суммы
        $refund = (new RefundPayment())->handle($payment->fresh(), 25000, 'Частичная отмена');

        $this->assertEquals(25000, $refund->amount_minor);

        // Платёж ещё не в Refunded — только часть возвращена
        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);

        // Транзакция возврата записана
        $this->assertDatabaseHas('payment_transactions', [
            'payment_id'   => $payment->id,
            'type'         => 'refund',
            'amount_minor' => 25000,
        ]);
    }

    public function test_cannot_refund_unpaid_payment(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(7, 0)->utc());
        $payment = (new CreatePayment())->handle($booking); // pending

        $this->expectException(\RuntimeException::class);
        (new RefundPayment())->handle($payment, 50000, 'причина');
    }

    public function test_cannot_refund_more_than_paid(): void
    {
        $booking = $this->makeBooking(Carbon::tomorrow()->setTime(6, 0)->utc());
        $payment = (new CreatePayment())->handle($booking);
        (new MarkPaymentPaid())->handle($payment);

        $this->expectException(\RuntimeException::class);
        (new RefundPayment())->handle($payment->fresh(), 99999);
    }

    // -------------------------------------------------------------------------
    // Идемпотентность вебхуков
    // -------------------------------------------------------------------------

    public function test_webhook_processed_once(): void
    {
        $processor = new WebhookProcessor();
        $callCount = 0;

        $handler = function () use (&$callCount) {
            $callCount++;
        };

        // Первый вызов — обрабатывается
        $result1 = $processor->process('yookassa', 'evt_001', ['type' => 'payment.succeeded'], $handler);
        $this->assertTrue($result1);
        $this->assertEquals(1, $callCount);

        // Второй вызов с тем же event_id — дубликат, пропускается
        $result2 = $processor->process('yookassa', 'evt_001', ['type' => 'payment.succeeded'], $handler);
        $this->assertFalse($result2);
        $this->assertEquals(1, $callCount); // handler НЕ вызван повторно
    }

    public function test_different_event_ids_processed_independently(): void
    {
        $processor = new WebhookProcessor();
        $callCount = 0;
        $handler   = function () use (&$callCount) { $callCount++; };

        $processor->process('yookassa', 'evt_100', ['a' => 1], $handler);
        $processor->process('yookassa', 'evt_101', ['a' => 2], $handler);

        $this->assertEquals(2, $callCount);
        $this->assertDatabaseCount('provider_webhook_events', 2);
    }

    public function test_same_event_id_different_provider_processed(): void
    {
        $processor = new WebhookProcessor();
        $callCount = 0;
        $handler   = function () use (&$callCount) { $callCount++; };

        // Один event_id, но у разных провайдеров — разные события
        $processor->process('yookassa', 'evt_999', ['p' => 'yk'], $handler);
        $processor->process('stripe',   'evt_999', ['p' => 'st'], $handler);

        $this->assertEquals(2, $callCount);
    }

    public function test_failed_handler_marks_event_failed(): void
    {
        $processor = new WebhookProcessor();

        $this->expectException(\RuntimeException::class);

        $processor->process('yookassa', 'evt_err', [], function () {
            throw new \RuntimeException('Ошибка обработки');
        });

        $event = ProviderWebhookEvent::where('event_id', 'evt_err')->first();
        $this->assertEquals('failed', $event->status);
    }
}
