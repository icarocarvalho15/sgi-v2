<?php

declare(strict_types=1);

namespace App\Modules\Production\Exceptions;

use DomainException;

final class InvalidProductionStateException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
