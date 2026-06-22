<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\PaymentTransaction;
use App\Support\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Вручную отметить платёж как оплаченный.
 * Используется как для ручных платежей (наличные/терминал),
 * так и для подтверждения от платёжного провайдера.
 */
class MarkPaymentPaid
{
    public function handle(
        Payment $payment,
        ?array  $rawPayload = null,
    ): Payment {
        if ($payment->status->isFinal()) {
            throw new RuntimeException(
                "Нельзя изменить платёж в статусе «{$payment->status->label()}»."
            );
        }

        return DB::transaction(function () use ($payment, $rawPayload) {
            $payment->update(['status' => PaymentStatus::Paid]);

            // Записываем транзакцию
            PaymentTransaction::create([
                'payment_id'    => $payment->id,
                'type'          => 'charge',
                'amount_minor'  => $payment->amount_minor,
                'currency_code' => $payment->currency_code,
                'raw_payload'   => $rawPayload,
            ]);

            return $payment->fresh();
        });
    }
}
