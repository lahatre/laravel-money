<?php

namespace Lahatre\Money\Casts;

use Lahatre\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Custom Cast for Money Value Objects.
 * 
 * Handles serialization between Database (integer/minor units) 
 * and Application (Money object).
 */
class MoneyCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @return Money|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromMinor($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @return int Target column value (minor units)
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
