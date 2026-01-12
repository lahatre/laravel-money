<?php

namespace Lahatre\Money\Support;

use InvalidArgumentException;

/**
 * Stateless wrapper around BCMath functions.
 * 
 * Ensures all operations work with strings to maintain arbitrary precision.
 * Implements custom rounding logic not native to BCMath.
 */
final class BigNumber
{
    // Native PHP modes mapped for consistency
    public const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    public const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    public const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    public const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;
    
    // Custom modes
    public const ROUND_UP = 10;   // Always round away from zero (Ceil)
    public const ROUND_DOWN = 11; // Always truncate (Floor)

    public static function add(string $a, string $b): string
    {
        self::validate($a, $b);
        return bcadd($a, $b, 0);
    }

    public static function sub(string $a, string $b): string
    {
        self::validate($a, $b);
        return bcsub($a, $b, 0);
    }

    public static function mul(string $a, string $b): string
    {
        self::validate($a, $b);
        return bcmul($a, $b, 0);
    }

    public static function div(string $a, string $b): string
    {
        self::validate($a, $b);
        
        if (bccomp($b, '0') === 0) {
            throw new InvalidArgumentException('Division by zero');
        }

        return bcdiv($a, $b, 0);
    }

    public static function compare(string $a, string $b): int
    {
        self::validate($a, $b);
        return bccomp($a, $b, 0);
    }

    public static function pow(string $base, string $exponent): string
    {
        self::validate($base, $exponent);
        return bcpow($base, $exponent, 0);
    }

    /**
     * Performs arbitrary precision rounding.
     * 
     * @param string $value The numeric string to round
     * @param int $precision Target decimal places
     * @param int $mode One of BigNumber::ROUND_* constants
     */
    public static function round(string $value, int $precision, int $mode = self::ROUND_HALF_UP): string
    {
        self::validate($value);

        // Handle ROUND_DOWN (Truncate)
        if ($mode === self::ROUND_DOWN) {
            return bcadd($value, '0', $precision);
        }

        // Handle Sign: Work with positive numbers, re-apply sign later
        if (bccomp($value, '0') === -1) {
            return '-' . self::round(ltrim($value, '-'), $precision, $mode);
        }

        // Handle ROUND_UP (Ceil for positives)
        if ($mode === self::ROUND_UP) {
            $truncated = bcadd($value, '0', $precision);
            // If value is greater than truncated, add 1 unit of precision
            if (bccomp($value, $truncated, strlen($value)) > 0) {
                $unit = bcdiv('1', bcpow('10', (string) $precision), $precision);
                return bcadd($truncated, $unit, $precision);
            }
            return $truncated;
        }

        // Handle ROUND_HALF_UP (Standard Banking)
        if ($mode === self::ROUND_HALF_UP) {
            $fraction = bcdiv('5', bcpow('10', (string) ($precision + 1)), $precision + 1);
            $withFraction = bcadd($value, $fraction, $precision + 1);
            
            return bcadd($withFraction, '0', $precision);
        }

        // Fallback to PHP native for unsupported modes (e.g., HALF_EVEN)
        $float = (float) $value;
        $rounded = round($float, $precision, $mode);
        
        return number_format($rounded, $precision, '.', '');
    }

    private static function validate(string ...$values): void
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                throw new InvalidArgumentException("Invalid numeric string: {$value}");
            }
        }
    }
}
