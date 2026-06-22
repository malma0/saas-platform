<?php

namespace App\Support\Money;

use InvalidArgumentException;

/**
 * Value-object для денег.
 *
 * Правило: деньги хранятся только в минимальных единицах (копейки, центы).
 * Никогда не используем float/double — только int (BIGINT в БД).
 *
 * @immutable
 */
final class Money
{
    public function __construct(
        public readonly int $amountMinor,
        public readonly string $currencyCode,
    ) {
        if (strlen($currencyCode) !== 3) {
            throw new InvalidArgumentException(
                "Currency code must be 3 characters, got: {$currencyCode}"
            );
        }
        if (strtoupper($currencyCode) !== $currencyCode) {
            throw new InvalidArgumentException(
                "Currency code must be uppercase: {$currencyCode}"
            );
        }
    }

    /**
     * Создать из минорных единиц.
     */
    public static function of(int $amountMinor, string $currencyCode): self
    {
        return new self($amountMinor, $currencyCode);
    }

    /**
     * Создать из рублей/долларов (перевести в копейки/центы).
     * Использовать только для ввода от пользователя, не для хранения.
     */
    public static function fromMajor(float $major, string $currencyCode, int $subunitFactor = 100): self
    {
        return new self((int) round($major * $subunitFactor), $currencyCode);
    }

    /**
     * Сложение двух сумм в одной валюте.
     */
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor + $other->amountMinor, $this->currencyCode);
    }

    /**
     * Вычитание.
     */
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor - $other->amountMinor, $this->currencyCode);
    }

    /**
     * Умножение (для расчёта стоимости).
     */
    public function multiply(int|float $factor): self
    {
        return new self((int) round($this->amountMinor * $factor), $this->currencyCode);
    }

    /**
     * Форматирование для отображения (например: «1 500,00 ₽»).
     */
    public function format(int $subunitFactor = 100, string $locale = 'ru_RU'): string
    {
        $major = $this->amountMinor / $subunitFactor;

        return number_format($major, 2, ',', ' ') . ' ' . $this->currencySymbol();
    }

    /**
     * Сумма в «больших» единицах (для отображения).
     */
    public function toMajor(int $subunitFactor = 100): float
    {
        return $this->amountMinor / $subunitFactor;
    }

    public function isZero(): bool
    {
        return $this->amountMinor === 0;
    }

    public function isPositive(): bool
    {
        return $this->amountMinor > 0;
    }

    public function isNegative(): bool
    {
        return $this->amountMinor < 0;
    }

    public function equals(Money $other): bool
    {
        return $this->amountMinor === $other->amountMinor
            && $this->currencyCode === $other->currencyCode;
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currencyCode !== $other->currencyCode) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currencyCode} and {$other->currencyCode}"
            );
        }
    }

    private function currencySymbol(): string
    {
        return match ($this->currencyCode) {
            'RUB' => '₽',
            'USD' => '$',
            'EUR' => '€',
            default => $this->currencyCode,
        };
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
