<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Saving decimal values such as monetary values should be saved as cent so
 * we can use this to convert dollar input to cent
 *
 * Set - Multiply any decimal values by 100 in order to save them as integers
 *
 * Get - Divide the integer values by 100 to return original value
 */
class DecimalToIntCast implements CastsAttributes
{
    public function set($model, string $key, $value, array $attributes)
    {
        return $value !== null ? $value * 100 : null;
    }

    public function get($model, string $key, $value, array $attributes)
    {
        return $value !== null ? $value / 100 : null;
    }
}
