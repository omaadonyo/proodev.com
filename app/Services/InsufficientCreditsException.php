<?php

namespace App\Services;

use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    public function __construct(public int $required = 1)
    {
        parent::__construct('You do not have enough credits. Please purchase more credits.');
    }
}
