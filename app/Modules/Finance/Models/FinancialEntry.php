<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CRM\Models\Partner;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Título Financeiro (Contas a Receber / Contas a Pagar).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $type
 * @property string $entry_number
 * @property string|null $partner_id
 * @property string|null $chart_of_account_id
 * @property string|null $cost_center_id
 * @property string|null $bank_account_id
 * @property string $status
 * @property string $description
 * @property string $original_amount
 * @property string $discount_amount
 * @property string $interest_fine_amount
 * @property string $net_amount
 * @property string $paid_amount
 * @property string $balance_due
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property \Illuminate\Support\Carbon|null $payment_date
 * @property \Illuminate\Support\Carbon $competence_date
 * @property int $installment_number
 * @property int $total_installments
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $notes
 */
class FinancialEntry extends TenantModel
{
    use SoftDeletes;

    protected $table = 'financial_entries';

    protected $fillable = [
        'type',
        'entry_number',
        'partner_id',
        'chart_of_account_id',
        'cost_center_id',
        'bank_account_id',
        'status',
        'description',
        'original_amount',
        'discount_amount',
        'interest_fine_amount',
        'net_amount',
        'paid_amount',
        'balance_due',
        'issue_date',
        'due_date',
        'payment_date',
        'competence_date',
        'installment_number',
        'total_installments',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'original_amount' => 'string',
        'discount_amount' => 'string',
        'interest_fine_amount' => 'string',
        'net_amount' => 'string',
        'paid_amount' => 'string',
        'balance_due' => 'string',
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'competence_date' => 'date',
        'installment_number' => 'integer',
        'total_installments' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id', 'id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinancialPayment::class, 'financial_entry_id', 'id');
    }
}
