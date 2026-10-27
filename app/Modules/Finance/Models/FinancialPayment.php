<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Finance\Enums\PaymentMethod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $financial_entry_id
 * @property string $financial_account_id
 * @property PaymentMethod $payment_method
 * @property CarbonInterface $payment_date
 * @property string $amount
 * @property string $interest_paid
 * @property string $fine_paid
 * @property string $discount_applied
 * @property string $net_amount
 * @property bool $is_reversed
 * @property string|null $reversal_reason
 * @property CarbonInterface|null $reversed_at
 * @property string|null $notes
 */
final class FinancialPayment extends TenantModel
{
    protected $table = 'financial_payments';

    protected $fillable = [
        'financial_entry_id',
        'financial_account_id',
        'payment_method',
        'payment_date',
        'amount',
        'interest_paid',
        'fine_paid',
        'discount_applied',
        'net_amount',
        'is_reversed',
        'reversal_reason',
        'reversed_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'interest_paid' => 'decimal:2',
            'fine_paid' => 'decimal:2',
            'discount_applied' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'is_reversed' => 'boolean',
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FinancialEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(FinancialEntry::class, 'financial_entry_id');
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }
}
