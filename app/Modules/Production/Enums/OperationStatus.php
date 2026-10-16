<?php

declare(strict_types=1);

namespace App\Modules\Production\Enums;

enum OperationStatus: string
{
    case Pending = 'PENDING';
    case InProgress = 'IN_PROGRESS';
    case Paused = 'PAUSED';
    case Completed = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::InProgress => 'Em Andamento',
            self::Paused => 'Pausada',
            self::Completed => 'Concluída',
        };
    }
}
