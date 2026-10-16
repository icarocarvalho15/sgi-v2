<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Core\Tenancy\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Classe base obrigatória para todas as Models de Domínio Operacional do SGI v2.0.
 *
 * @property string $id
 * @property string $tenant_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
abstract class TenantModel extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasUuids;

    /**
     * O tenant_id é protegido contra Mass Assignment e preenchido exclusivamente pelo TenantContext.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'tenant_id',
    ];
}
