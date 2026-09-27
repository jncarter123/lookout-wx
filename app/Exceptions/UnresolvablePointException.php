<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The point could not be placed in a county or forecast zone — NWS did not answer for
 * it, or it lies outside NWS coverage. Either way no alert can be matched to it, which
 * must not be reported as "no alerts here".
 */
class UnresolvablePointException extends RuntimeException
{
    public function __construct(float $latitude, float $longitude)
    {
        parent::__construct("Unable to resolve {$latitude},{$longitude} to a county or forecast zone.");
    }
}
