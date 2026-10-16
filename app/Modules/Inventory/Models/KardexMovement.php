<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Models\User;
use App\Modules\Inventory\Enums\KardexMovementType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Razão imutável de movimentação de estoque (Append-Only Ledger).
 * NUNCA sofre UPDATE ou DELETE.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $movement_number
 * @property string $warehouse_id
 * @property string $item_id
 * @property KardexMovementType $movement_type
 * @property string $quantity
 * @property string $unit_cost
 * @property string $total_cost
 * @property string $previous_balance
 * @property string $resulting_balance
 * @property string $previous_average_cost
 * @property string $resulting_average_cost
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $document_number
 * @property string|null $justification
 * @property string|null $user_id
 * @property CarbonInterface $created_at
 */
final class KardexMovement extends TenantModel
{
    protected $table = 'kardex_movements';

    public $timestamps = false;

    protected $fillable = [
        'movement_number',
        'warehouse_id',
        'item_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'previous_balance',
        'resulting_balance',
        'previous_average_cost',
        'resulting_average_cost',
        'reference_type',
        'reference_id',
        'document_number',
        'justification',
        'user_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_type' => KardexMovementType::class,
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'previous_balance' => 'decimal:4',
            'resulting_balance' => 'decimal:4',
            'previous_average_cost' => 'decimal:4',
            'resulting_average_cost' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(static function (self $model): void {
            if ($model->created_at === null) {
                $model->created_at = now();
            }
        });

        // Bloqueio categórico contra qualquer edição em tempo de execução
        self::updating(static function (): void {
            throw new \LogicException('Movimentações de Kardex são imutáveis e não podem ser alteradas.');
        });

        self::deleting(static function (): void {
            throw new \LogicException('Movimentações de Kardex são imutáveis e não podem ser excluídas.');
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
