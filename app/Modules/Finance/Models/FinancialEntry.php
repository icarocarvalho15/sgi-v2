<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Models\SalesOrder;
use App\Modules\Finance\Enums\EntryStatus;
use App\Modules\Finance\Enums\EntryType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property EntryType $entry_type
 * @property string|null $partner_id
 * @property string $chart_of_account_id
 * @property string|null $cost_center_id
 * @property string|null $sales_order_id
 * @property string $document_number
 * @property string $title
 * @property int $installment_number
 * @property int $total_installments
 * @property CarbonInterface $issue_date
 * @property CarbonInterface $due_date
 * @property CarbonInterface $competence_date
 * @property string $original_amount
 * @property string $balance_amount
 * @property string $paid_amount
 * @property string $interest_amount
 * @property string $fine_amount
 * @property string $discount_amount
 * @property EntryStatus $status
 * @property string|null $notes
 */
final class FinancialEntry extends TenantModel
{
    protected $table = 'financial_entries';

    protected $fillable = [
        'entry_type',
        'partner_id',
        'chart_of_account_id',
        'cost_center_id',
        'sales_order_id',
        'document_number',
        'title',
        'installment_number',
        'total_installments',
        'issue_date',
        'due_date',
        'competence_date',
        'original_amount',
        'balance_amount',
        'paid_amount',
        'interest_amount',
        'fine_amount',
        'discount_amount',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_type' => EntryType::class,
            'status' => EntryStatus::class,
            'installment_number' => 'integer',
            'total_installments' => 'integer',
            'issue_date' => 'date',
            'due_date' => 'date',
            'competence_date' => 'date',
            'original_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'fine_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    /**
     * @return BelongsTo<CostCenter, $this>
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    /**
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    /**
     * @return HasMany<FinancialPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(FinancialPayment::class, 'financial_entry_id')->latest('payment_date');
    }
}
