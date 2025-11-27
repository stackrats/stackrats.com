<?php

namespace App\Enums;

enum Currencies: string
{
    case NZD = 'NZD';
    case AUD = 'AUD';
    case USD = 'USD';
    case GBP = 'GBP';
    case EUR = 'EUR';
    case CAD = 'CAD';

    public function label(): string
    {
        return match ($this) {
            self::NZD => 'New Zealand Dollar',
            self::AUD => 'Australian Dollar',
            self::USD => 'United States Dollar',
            self::GBP => 'British Pound',
            self::EUR => 'Euro',
            self::CAD => 'Canadian Dollar',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::NZD, self::AUD, self::USD, self::CAD => '$',
            self::GBP => '£',
            self::EUR => '€',
        };
    }
}
