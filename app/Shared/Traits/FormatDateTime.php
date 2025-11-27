<?php

namespace App\Shared\Traits;

use App\Enums\Timezones;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;

trait FormatDateTime
{
    /**
     * Format Carbon or CarbonImmutable datetime to users local timezone
     */
    public function parseUtcDateTimeAsLocal(
        CarbonInterface $carbon,
        ?User $user = null,
        string $format = 'Y-m-d H:i:s'
    ): string {
        $authUser = Auth::user();
        $targetUser = $user ?? $authUser;

        $timezone = $targetUser?->userSetting?->timezone->value ?? Timezones::PACIFIC_AUCKLAND;

        return $carbon->setTimezone($timezone)->format($format);
    }

    /**
     * Format string local datetime to UTC timezone
     */
    public function parseLocalDateTimeAsUtc(
        string $dateTime,
        ?User $user = null
    ): string {
        $authUser = Auth::user();
        $targetUser = $user ?? $authUser;

        $timezone = $targetUser?->userSetting?->timezone->value ?? Timezones::PACIFIC_AUCKLAND;

        return Carbon::parse($dateTime, $timezone)->setTimezone(Timezones::UTC->value)->format('Y-m-d H:i:s');
    }
}
