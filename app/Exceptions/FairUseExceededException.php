<?php

namespace App\Exceptions;

class FairUseExceededException extends \RuntimeException
{
    public function __construct(
        string $message = 'Fair-use limit exceeded.',
        public readonly string $reason = 'unknown',
        public readonly ?int $limit = null,
        public readonly ?int $current = null,
    ) {
        parent::__construct($message);
    }
}
