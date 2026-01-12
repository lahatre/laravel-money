<?php

namespace Lahatre\Money\Support;

use InvalidArgumentException;

/**
 * Wrapper bas niveau autour de BCMath
 * Tous les calculs retournent des strings
 * Toutes les entrées sont validées
 */
final class BigNumber
{
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
     * Arrondi avec mode spécifique
     * 
     * @param string $value Valeur à arrondir
     * @param int $precision Nombre de décimales
     * @param int $mode Mode d'arrondi (PHP_ROUND_*)
     */
    public static function round(string $value, int $precision, int $mode = PHP_ROUND_HALF_UP): string
    {
        self::validate($value);
        
        // BCMath ne supporte pas les modes d'arrondi natifs
        // On utilise la fonction PHP round() qui elle les supporte
        $float = (float) $value;
        $rounded = round($float, $precision, $mode);
        
        // Retour en string avec la précision exacte
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
