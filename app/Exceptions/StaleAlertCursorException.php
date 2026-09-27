<?php

namespace App\Exceptions;

use RuntimeException;

class StaleAlertCursorException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cursor is older than the retained change history. Resync by requesting without "since".');
    }
}
