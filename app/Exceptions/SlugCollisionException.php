<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class SlugCollisionException extends RuntimeException
{
    public function __construct(
        string $message = 'Slug collision detected.',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
