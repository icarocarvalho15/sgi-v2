<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use InvalidArgumentException;

final class InvalidStockMovementException extends InvalidArgumentException
{
    // Exception customizada para validação semântica de movimentos de estoque
}
