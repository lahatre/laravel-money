<?php

namespace Lahatre\Money\ValueObjects;

use Lahatre\Money\Exceptions\NegativeMoneyException;
use Lahatre\Money\Support\BigNumber;
use InvalidArgumentException;
use Stringable;

/**
 * Value Object immutable représentant un montant monétaire
 * 
 * @example
 * $price = Money::from("19.99");
 * $total = $price->mul(3); // 59.97
 * $vat = $total->percentage(20); // 11.99
 */
final class Money implements Stringable
{
    /**
     * Montant en minor unit (centimes)
     * Ex: "1050" pour 10.50€
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
     * Factory: crée depuis un montant en format humain (major unit)
     * 
     * @param float|int|string $amount Montant en format humain (10.50)
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
     * Factory: crée depuis un montant déjà en minor unit
     * 
     * @param int|string $minorAmount Montant en centimes (1050)
     * @internal Utilisé par MoneyCast
     */
    public static function fromMinor(int|string $minorAmount): self
    {
        $precision = config('money.precision', 2);
        return new self((string) $minorAmount, $precision);
    }

    /**
     * Factory: zéro
     */
    public static function zero(): self
    {
        return self::from('0');
    }

    /**
     * Addition
     * 
     * @param Money|int|float|string $other
     */
    public function add(Money|int|float|string $other): self
    {
        $other = $this->ensureMoney($other);
        $result = BigNumber::add($this->minorAmount, $other->minorAmount);

        return new self($result, $this->precision);
    }

    /**
     * Soustraction
     * 
     * @param Money|int|float|string $other
     */
    public function sub(Money|int|float|string $other): self
    {
        $other = $this->ensureMoney($other);
        $result = BigNumber::sub($this->minorAmount, $other->minorAmount);

        return new self($result, $this->precision);
    }

    /**
     * Multiplication par un facteur entier
     * 
     * @param int $factor Multiplicateur (entier uniquement)
     * 
     * @example
     * $itemPrice = Money::from("19.99");
     * $total = $itemPrice->mul(3); // 59.97
     */
    public function mul(int $factor): self
    {
        $result = BigNumber::mul($this->minorAmount, (string) $factor);

        return new self($result, $this->precision);
    }

    /**
     * Division par un diviseur entier
     * 
     * ⚠️ Utilise le mode d'arrondi configuré (défaut: ROUND_HALF_UP)
     * 
     * @param int $divisor Diviseur (entier uniquement, != 0)
     * @throws InvalidArgumentException Si diviseur = 0
     * 
     * @example
     * $total = Money::from("100.00");
     * $perPerson = $total->div(3); // 33.33
     */
    public function div(int $divisor): self
    {
        if ($divisor === 0) {
            throw new InvalidArgumentException('Division by zero');
        }

        // Division en minor unit avec arrondi
        $majorAmount = $this->toMajorUnit();
        $result = bcdiv($majorAmount, (string) $divisor, $this->precision + 2);
        
        // Arrondi selon le mode configuré
        $roundingMode = config('money.rounding_mode', PHP_ROUND_HALF_UP);
        $rounded = BigNumber::round($result, $this->precision, $roundingMode);
        
        return self::from($rounded);
    }

    /**
     * Calcul de pourcentage
     * 
     * @param int|float $rate Taux (5 pour 5%, 5.5 pour 5.5%)
     * @param int|null $roundingMode Mode d'arrondi (null = config)
     * 
     * @example
     * $price = Money::from("100.00");
     * $vat = $price->percentage(20); // 20.00
     * $discount = $price->percentage(15.5); // 15.50
     */
    public function percentage(int|float $rate, ?int $roundingMode = null): self
    {
        $roundingMode ??= config('money.rounding_mode', PHP_ROUND_HALF_UP);
        
        // Calcul en major unit pour précision maximale
        $major = $this->toMajorUnit();
        $rateStr = (string) $rate;
        
        // major × rate ÷ 100
        $result = bcmul($major, $rateStr, $this->precision + 2);
        $result = bcdiv($result, '100', $this->precision + 2);
        
        // Arrondi
        $rounded = BigNumber::round($result, $this->precision, $roundingMode);
        
        return self::from($rounded);
    }

    /**
     * Égalité
     */
    public function equals(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) === 0;
    }

    /**
     * Plus grand que
     */
    public function greaterThan(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) > 0;
    }

    /**
     * Plus grand ou égal
     */
    public function greaterThanOrEqual(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) >= 0;
    }

    /**
     * Plus petit que
     */
    public function lessThan(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) < 0;
    }

    /**
     * Plus petit ou égal
     */
    public function lessThanOrEqual(Money $other): bool
    {
        return BigNumber::compare($this->minorAmount, $other->minorAmount) <= 0;
    }

    /**
     * Est zéro ?
     */
    public function isZero(): bool
    {
        return BigNumber::compare($this->minorAmount, '0') === 0;
    }

    /**
     * Est positif ?
     */
    public function isPositive(): bool
    {
        return BigNumber::compare($this->minorAmount, '0') > 0;
    }

    /**
     * Est négatif ?
     */
    public function isNegative(): bool
    {
        return BigNumber::compare($this->minorAmount, '0') < 0;
    }

    /**
     * Retourne le montant en minor unit (pour DB)
     * 
     * @internal Utilisé par MoneyCast
     */
    public function getMinorAmount(): string
    {
        return $this->minorAmount;
    }

    /**
     * Retourne le montant en major unit (format humain)
     * 
     * @return string Ex: "10.50"
     */
    public function getAmount(): string
    {
        return $this->toMajorUnit();
    }

    /**
     * Formatage pour l'affichage
     * 
     * @return string Ex: "10.50" ou "1050" (si precision=0)
     */
    public function format(): string
    {
        $major = $this->toMajorUnit();
        
        if ($this->precision === 0) {
            return $major;
        }

        return number_format((float) $major, $this->precision, '.', '');
    }

    /**
     * Cast en string
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Conversion minor unit → major unit
     * Ex: "1050" → "10.50"
     */
    private function toMajorUnit(): string
    {
        if ($this->precision === 0) {
            return $this->minorAmount;
        }

        return bcdiv($this->minorAmount, $this->divisor, $this->precision);
    }

    /**
     * Conversion major unit → minor unit
     * Ex: "10.50" → "1050"
     */
    private static function toMinorUnit(string $major, string $divisor, int $precision): string
    {
        if ($precision === 0) {
            return $major;
        }

        $result = bcmul($major, $divisor, $precision);
        return bcadd($result, '0', 0);
    }

    /**
     * Normalisation de l'entrée utilisateur
     */
    private static function normalizeInput(float|int|string $value): string
    {
        $value = (string) $value;
        
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Invalid numeric value: {$value}");
        }

        return $value;
    }

    /**
     * Coercion: convertit toute valeur en Money
     */
    private function ensureMoney(Money|float|int|string $value): self
    {
        return $value instanceof self ? $value : self::from($value);
    }

    /**
     * Validation: rejette les négatifs si configuré
     */
    private function guardNegative(): void
    {
        if (!config('money.allow_negative', false) && $this->isNegative()) {
            throw NegativeMoneyException::notAllowed($this->toMajorUnit());
        }
    }
}