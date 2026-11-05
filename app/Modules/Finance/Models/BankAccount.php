<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Conta Bancária / Caixa Físico.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string|null $bank_code
 * @property string|null $agency
 * @property string|null $account_number
 * @property string $type
 * @property string $initial_balance
 * @property string $current_balance
 * @property bool $is_default
 * @property bool $is_active
 */
class BankAccount extends TenantModel
{
    use SoftDeletes;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'name',
        'bank_code',
        'agency',
        'account_number',
        'type',
        'initial_balance',
        'current_balance',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'string',
        'current_balance' => 'string',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class, 'bank_account_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinancialPayment::class, 'bank_account_id', 'id');
    }
}
