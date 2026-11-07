<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * How long a guest link grants access, counted from its creation.
 */
enum GuestLinkDuration: int
{
    case OneDay = 1;
    case OneWeek = 7;
    case TwoWeeks = 14;

    public function expiresAt(): CarbonInterface
    {
        return now()->addDays($this->value);
    }
}
