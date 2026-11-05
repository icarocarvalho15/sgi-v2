<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PartnerAddress represents multiple addresses per Partner.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $partner_id
 * @property string $type
 * @property string $postal_code
 * @property string $street
 * @property string $number
 * @property string|null $complement
 * @property string $neighborhood
 * @property string $city
 * @property string $state
 * @property string|null $ibge_code
 * @property bool $is_default
 */
class PartnerAddress extends TenantModel
{
    protected $table = 'partner_addresses';

    protected $fillable = [
        'partner_id',
        'type',
        'postal_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'ibge_code',
        'is_default',
    ];

    protected $attributes = [
        'type' => 'main',
        'is_default' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
