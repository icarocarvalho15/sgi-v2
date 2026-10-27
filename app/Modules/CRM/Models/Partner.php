<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CRM\Enums\PartnerType;
use App\Modules\CRM\Enums\PersonType;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property PartnerType $partner_type
 * @property PersonType $person_type
 * @property string $document_number
 * @property string|null $state_registration
 * @property string $name
 * @property string|null $trade_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $notes
 * @property bool $is_active
 */
final class Partner extends TenantModel
{
    protected $table = 'partners';

    protected $fillable = [
        'partner_type',
        'person_type',
        'document_number',
        'state_registration',
        'name',
        'trade_name',
        'email',
        'phone',
        'notes',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'partner_type' => PartnerType::class,
            'person_type' => PersonType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PartnerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(PartnerAddress::class, 'partner_id');
    }

    /**
     * @return HasMany<CrmDeal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'partner_id');
    }

    /**
     * @return HasMany<Quote, $this>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'partner_id');
    }

    /**
     * @return HasMany<SalesOrder, $this>
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'partner_id');
    }
}
