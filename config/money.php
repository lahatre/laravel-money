<?php

/**
 * ============================================================================
 * LAHATRE MONEY - Configuration
 * ============================================================================
 * 
 * Architecture:
 * - Input: Human format (e.g., "10.50")
 * - Internal: Minor units string (e.g., "1050")
 * - Math: BCMath on minor units (arbitrary precision)
 * - Storage: Integer (BigInt)
 * 
 * Invariants:
 * - Immutable by design
 * - No floating point arithmetic
 * - Strict validation (numeric strings)
 */

use Lahatre\Money\Support\BigNumber;

return [
    /**
     * Currency Precision (decimals)
     * 
     * Common values:
     * - 2: EUR, USD, GBP
     * - 0: XOF, JPY, KRW
     * - 3: KWD, BHD, TND
     */
    'precision' => env('MONEY_PRECISION', 2),

    /**
     * Default Rounding Mode
     * 
     * Controls how extra precision is handled during division/percentage.
     * 
     * Options:
     * - BigNumber::ROUND_HALF_UP   : Standard banker's rounding (0.5 -> up)
     * - BigNumber::ROUND_UP        : Always rounds away from zero (Ceil)
     * - BigNumber::ROUND_DOWN      : Truncates extra decimals (Floor)
     */
    'rounding_mode' => BigNumber::ROUND_HALF_UP,

    /**
     * Negative Amounts Policy
     * 
     * false : Strict mode. Throws exception on negative results.
     * true  : Allow debts, overdrafts, and negative balances.
     */
    'allow_negative' => false,
];
