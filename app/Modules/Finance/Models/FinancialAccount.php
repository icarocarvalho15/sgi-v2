<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Finance\Enums\AccountType;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property AccountType $type
 * @property string|null $bank_name
 * @property string|null $agency_number
 * @property string|null $account_number
 * @property string $current_balance
 * @property bool $is_active
 * @property bool $is_default
 */
final class FinancialAccount extends TenantModel
{
    protected $table = 'financial_accounts';

    protected $fillable = [
        'name',
        'type',
        'bank_name',
        'agency_number',
        'account_number',
        'current_balance',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return HasMany<FinancialPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(FinancialPayment::class, 'financial_account_id');
    }
}
