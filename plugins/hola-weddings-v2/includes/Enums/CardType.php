<?php

namespace HW\Enums;

enum CardType: string
{
    case VISA        = '001';
    case MASTERCARD  = '002';
    case AMEX        = '003';
    case DISCOVER    = '004';
    case DINERS_CLUB = '005';

    public function label(): string
    {
        return match ($this) {
            self::VISA        => 'Visa',
            self::MASTERCARD  => 'MasterCard',
            self::AMEX        => 'AMEX',
            self::DISCOVER    => 'Discover',
            self::DINERS_CLUB => 'Diners Club',
        };
    }

    /**
     * Useful for select inputs, APIs, etc.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn ($carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }
}