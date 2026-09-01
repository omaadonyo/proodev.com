<?php

namespace App\Services;

use App\Models\PlagiarismStrike;
use RuntimeException;

/**
 * Thrown when the plagiarism guard rejects a repository claim. Carries the
 * strike so the caller can tailor the message (warning vs ban).
 */
class PlagiarismDetectedException extends RuntimeException
{
    public function __construct(public PlagiarismStrike $strike)
    {
        parent::__construct($strike->reason);
    }
}
