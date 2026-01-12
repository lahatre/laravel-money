<?php

namespace Lahatre\Money;

use Lahatre\Money\Exceptions\NegativeMoneyException;
use Lahatre\Money\Support\BigNumber;
use InvalidArgumentException;
use Stringable;

/**
 * Immutable Value Object representing a monetary amount.
 * 
 * Enforces precision, rounding rules, and arithmetic safety.
 * 
 * @example
 * $price = Money::from("19.99");
 * $total = $price->mul(3);
 * $vat = $total->percentage(20);
 */
final class Money implements Stringable
{
    /**
     * Amount in minor units (e.g., "1050" for 10.50)
     */
    private string $minorAmount;

    private int $precision;
    private string $divisor;

    private function __construct(string $minorAmount, int $precision)
    {
        $this->minorAmount = $minorAmount;
        $this->precision = $precision;
        $this->divisor = BigNumber::pow('10', (string) $precision);
        
        $this->guardNegative();
    }

    /**
     * Create from human-readable format (Major Units).
     * 
     * @param $amount int|float|string (e.g., 10.50, "10.50")
     */
    public static function from(float|int|string $amount): self
    {
        $precision = config('money.precision', 2);
        $amount = self::normalizeInput($amount);
        
        $divisor = BigNumber::pow('10', (string) $precision);
        $minorAmount = self::toMinorUnit($amount, $divisor, $precision);

        return new self($minorAmount, $precision);
    }

    /**
     * Create from minor units (integers).
     * 
     * @param $minorAmount int|string (e.g., 1050 for 10.50)
     * @internal Used by MoneyCast
     */
    public static function fromMinor(int|string $minorAmount): self
    {
        $precision = config('money.precision', 2);
        return new self((string) $minorAmount, $precision);
    }

    /**
     * Create a zero value instance.
     */
    public static function zero(): self
    {
        return self::from('0');
    }

    /**
     * Add another money value.
     */
    public function add(Money|int|float|string $other): self
    {
        $other = $this->ensureMoney($other);
        $result = BigNumber::add($this->minorAmount, $other->minorAmount);

        return new self($result, $this->precision);
    }

    /**
     * Subtract another money value.
     */
    public function sub(Money|int|float|string $other): self
    {
        $other = $this->ensureMoney($other);
        $result = BigNumber::sub($this->minorAmount, $other->minorAmount);

        return new self($result, $this->precision);
    }

    /**
     * Multiply by a factor.
     * 
     * @param $multiplier Factor (e.g., 3, 1.5, "0.20")
     * @param int|null $roundingMode Optional override for rounding logic
     */
    public function mul(int|float|string $multiplier, ?int $roundingMode = null): self
    {
        $roundingMode ??= config('money.rounding_mode', BigNumber::ROUND_HALF_UP);
        $multiplier = (string) $multiplier;

        // Calculate on minor units with high precision buffer
        $result = bcmul($this->minorAmount, $multiplier, 10);

        $rounded = BigNumber::round($result, 0, $roundingMode);

        return new self($rounded, $this->precision);
    }

    /**
     * Divide by a divisor.
     * 
     * @param $divisor Divisor (e.g., 3)
     * @param int|null $roundingMode Optional override for rounding logic
     * @throws InvalidArgumentException If divisor is zero
     */
    public function div(int|float|string $divisor, ?int $roundingMode = null): self
    {
        if ((string)$divisor === '0') {
            throw new InvalidArgumentException('Division by zero');
        }

        $roundingMode ??= config('money.rounding_mode', BigNumber::ROUND_HALF_UP);
        $divisor = (string) $divisor;

        // Calculate directly on minor units
        $result = bcdiv($this->minorAmount, $divisor, 10);
        
        $rounded = BigNumber::round($result, 0, $roundingMode);
        
        return new self($rounded, $this->precision);
    }

    /**
     * Calculate percentage of the amount.
     * 
     * @param $rate Rate (e.g., 20 for 20%)
     */
    public function percentage(int|float $rate, ?int $roundingMode = null): self
    {
        $roundingMode ??= config('money.rounding_mode', PHP_ROUND_HALF_UP);
        
        // Use major units for calculation stability
        $major = $this->toMajorUnit();
        $rateStr = (string) $rate;
        
        // major * rate / 100
        $result = bcmul($major, $rateStr, $this->precision + 2);
        $result = bcdiv($result, '100', $this->precision + 2);
        
        $rounded = BigNumber::round($result, $this->precision, $roundingMode);
        
        return self::from($rounded);
    }

    // Comparison Methods

    public function equals(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) === 0;
    }

    public function greaterThan(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) > 0;
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) >= 0;
    }

    public function lessThan(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) < 0;
    }

    public function lessThanOrEqual(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) <= 0;
    }

    public function isZero(): bool
    {
        return BigNumber::compare($this->minorAmount, '0') === 0;
    }

    public function isPositive(): bool
    {
        return BigNumber::compare($this->minorAmount, '0') > 0;
    }

    public function isNegative(): bool
    {
        return BigNumber::compare($this->minorAmount, '0') < 0;
    }

    /**
     * Get the amount in minor units (for DB storage).
     */
    public function getMinorAmount(): string
    {
        return $this->minorAmount;
    }

    /**
     * Get the amount in major units (human readable).
     */
    public function getAmount(): string
    {
        return $this->toMajorUnit();
    }

    /**
     * Format for display.
     * 
     * @return string e.g., "10.50"
     */
    public function format(): string
    {
        $major = $this->toMajorUnit();
        
        if ($this->precision === 0) {
            return $major;
        }

        return number_format((float) $major, $this->precision, '.', '');
    }

    public function __toString(): string
    {
        return $this->format();
    }

    // Internal Helpers

    private function toMajorUnit(): string
    {
        if ($this->precision === 0) {
            return $this->minorAmount;
        }

        return bcdiv($this->minorAmount, $this->divisor, $this->precision);
    }

    private static function toMinorUnit(string $major, string $divisor, int $precision): string
    {
        if ($precision === 0) {
            return $major;
        }

        $result = bcmul($major, $divisor, $precision);
        return bcadd($result, '0', 0);
    }

    private static function normalizeInput(float|int|string $value): string
    {
        $value = (string) $value;
        
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Invalid numeric value: {$value}");
        }

        return $value;
    }

    private function ensureMoney(Money|float|int|string $value): self
    {
        return $value instanceof self ? $value : self::from($value);
    }

    private function guardNegative(): void
    {
        if (!config('money.allow_negative', false) && $this->isNegative()) {
            throw NegativeMoneyException::notAllowed($this->toMajorUnit());
        }
    }
}