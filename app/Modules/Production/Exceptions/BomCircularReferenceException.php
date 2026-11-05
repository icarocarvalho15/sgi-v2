<?php

declare(strict_types=1);

namespace App\Modules\Production\Exceptions;

use RuntimeException;

final class BomCircularReferenceException extends RuntimeException
{
    public function __construct(string $message = 'Erro de integridade industrial: Detectada dependência circular na Ficha Técnica (BOM). Um item não pode ser componente de si mesmo ou de seus subconjuntos.')
    {
        parent::__construct($message);
    }
}
