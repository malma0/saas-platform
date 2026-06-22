<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\PaymentTransaction;
use App\Domain\Payments\Models\Refund;
use App\Support\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Оформить возврат по платежу.
 * Поддерживает частичные возвраты (amount_minor < payment.amount_minor).
 */
class RefundPayment
{
    public function handle(
        Payment $payment,
        int     $amountMinor,
        string  $reason = '',
        ?int    $initiatedBy = null,
    ): Refund {
        if (!$payment->isPaid()) {
            throw new RuntimeException(
                "Возврат возможен только для оплаченных платежей. Статус: «{$payment->status->label()}»."
            );
        }

        $alreadyRefunded = $payment->refundedAmount();
        $available = $payment->amount_minor - $alreadyRefunded;

        if ($amountMinor <= 0 || $amountMinor > $available) {
            throw new RuntimeException(
                "Неверная сумма возврата: {$amountMinor}. Доступно для возврата: {$available}."
            );
        }

        return DB::transaction(function () use ($payment, $amountMinor, $reason, $initiatedBy) {
            $refund = Refund::create([
                'payment_id'    => $payment->id,
                'amount_minor'  => $amountMinor,
                'currency_code' => $payment->currency_code,
                'reason'        => $reason,
                'status'        => 'completed',   // ручной возврат — сразу завершён
                'initiated_by'  => $initiatedBy,
            ]);

            // Транзакция возврата
            PaymentTransaction::create([
                'payment_id'    => $payment->id,
                'type'          => 'refund',
                'amount_minor'  => $amountMinor,
                'currency_code' => $payment->currency_code,
            ]);

            // Если вернули всю сумму — помечаем платёж как Refunded
            $totalRefunded = $payment->refundedAmount();
            if ($totalRefunded >= $payment->amount_minor) {
                $payment->update(['status' => PaymentStatus::Refunded]);
            }

            return $refund;
        });
    }
}
