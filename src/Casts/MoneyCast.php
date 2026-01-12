<?php

namespace Lahatre\Money\Casts;

use Lahatre\Money\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast Eloquent pour Money
 * 
 * Get: 1050 (int DB) → Money::fromMinor(1050) → "10.50"
 * Set: Money("10.50") → 1050 (int DB)
 */
class MoneyCast implements CastsAttributes
{
    /**
     * Cast depuis la DB vers Money
     *
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromMinor($value);
    }

    /**
     * Cast depuis Money vers la DB
     *
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value === null) {
            return 0;
        }

        if (!$value instanceof Money) {
            $value = Money::from($value);
        }

        return (int) $value->getMinorAmount();
    }
}
