<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Partner represents Clients (Clientes) and Suppliers (Fornecedores).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $type
 * @property string $person_type
 * @property string $name
 * @property string|null $trade_name
 * @property string $document_number
 * @property string|null $state_registration
 * @property string|null $municipal_registration
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property bool $is_tax_exempt
 * @property string|null $notes
 * @property bool $is_active
 */
class Partner extends TenantModel
{
    use SoftDeletes;

    protected $table = 'partners';

    protected $fillable = [
        'type',
        'person_type',
        'name',
        'trade_name',
        'document_number',
        'state_registration',
        'municipal_registration',
        'email',
        'phone',
        'whatsapp',
        'is_tax_exempt',
        'notes',
        'is_active',
    ];

    protected $attributes = [
        'type' => 'customer',
        'person_type' => 'company',
        'is_tax_exempt' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_tax_exempt' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartnerAddress::class, 'partner_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'customer_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'partner_id');
    }

    public function isCustomer(): bool
    {
        return in_array($this->type, ['customer', 'both'], true);
    }

    public function isSupplier(): bool
    {
        return in_array($this->type, ['supplier', 'both'], true);
    }
}
