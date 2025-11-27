<?php

namespace App\Enums;

enum InvoiceUnitTypes: string
{
    case QUANTITY = 'quantity';
    case HOURS = 'hours';
    case DAYS = 'days';
    case MONTHS = 'months';

    public function label(): string
    {
        return match ($this) {
            self::QUANTITY => 'Quantity',
            self::HOURS => 'Hours',
            self::DAYS => 'Days',
            self::MONTHS => 'Months',
        };
    }
}
