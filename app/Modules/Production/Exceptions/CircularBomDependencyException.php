<?php

declare(strict_types=1);

namespace App\Modules\Production\Exceptions;

use DomainException;

final class CircularBomDependencyException extends DomainException
{
    public function __construct(string $parentItemId, string $childItemId)
    {
        parent::__construct(
            sprintf(
                'Dependência circular detectada na Ficha Técnica: o item [%s] não pode conter o item [%s] como insumo direta ou indiretamente.',
                $parentItemId,
                $childItemId
            )
        );
    }
}
