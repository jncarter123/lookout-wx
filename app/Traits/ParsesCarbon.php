<?php

namespace App\Traits;

use Carbon\Carbon;

trait ParsesCarbon
{
    private function tryParseCarbon(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
