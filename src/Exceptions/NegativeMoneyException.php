<?php

namespace Lahatre\Money\Exceptions;

use InvalidArgumentException;

class NegativeMoneyException extends InvalidArgumentException
{
    public static function notAllowed(string $amount): self
    {
        return new self(
            "Negative money amounts are not allowed: {$amount}. " .
            "Set 'allow_negative' to true in config/money.php to enable."
        );
    }
}