<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\PaymentTransaction;
use App\Support\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Создать платёж для брони.
 * В MVP — только ручная отметка оплаты.
 * Будущие провайдеры (ЮKassa и т.д.) подключаются через тот же Action,
 * просто с другим $provider и $providerPaymentId.
 */
class CreatePayment
{
    public function handle(
        Booking $booking,
        string  $provider = 'manual',
        ?int    $collectedBy = null,
        ?string $providerPaymentId = null,
        ?string $notes = null,
    ): Payment {
        // Бронь не должна иметь уже оплаченного платежа
        $existingPaid = Payment::withoutGlobalScopes()
            ->where('booking_id', $booking->id)
            ->where('status', PaymentStatus::Paid->value)
            ->exists();

        if ($existingPaid) {
            throw new RuntimeException('Бронь уже оплачена.');
        }

        return DB::transaction(function () use ($booking, $provider, $collectedBy, $providerPaymentId, $notes) {
            $payment = Payment::create([
                'club_id'             => $booking->club_id,
                'booking_id'          => $booking->id,
                'amount_minor'        => $booking->amount_minor,
                'currency_code'       => $booking->currency_code,
                'status'              => PaymentStatus::Pending,
                'provider'            => $provider,
                'provider_payment_id' => $providerPaymentId,
                'collected_by'        => $collectedBy,
                'notes'               => $notes,
            ]);

            return $payment;
        });
    }
}
