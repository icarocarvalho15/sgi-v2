<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Baixa de Título Financeiro.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $financial_entry_id
 * @property string $bank_account_id
 * @property string $payment_method
 * @property string $amount
 * @property string $discount_amount
 * @property string $interest_fine_amount
 * @property \Illuminate\Support\Carbon $payment_date
 * @property string|null $user_id
 * @property string|null $external_transaction_id
 * @property string|null $notes
 */
class FinancialPayment extends TenantModel
{
    use SoftDeletes;

    protected $table = 'financial_payments';

    protected $fillable = [
        'financial_entry_id',
        'bank_account_id',
        'payment_method',
        'amount',
        'discount_amount',
        'interest_fine_amount',
        'payment_date',
        'user_id',
        'external_transaction_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'string',
        'discount_amount' => 'string',
        'interest_fine_amount' => 'string',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(FinancialEntry::class, 'financial_entry_id', 'id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id', 'id');
    }
}
