<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum WebhookEventStatus: string
{
    case Processed = 'PROCESSED';
    case Failed = 'FAILED';
    case Ignored = 'IGNORED';
}
