<?php

namespace App\Models;

use App\Enums\RecurringFrequencies;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringFrequency extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'label',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function label(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => RecurringFrequencies::from($attributes['name'])->label(),
        );
    }
}
