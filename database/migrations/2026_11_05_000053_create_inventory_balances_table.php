<?php

declare(strict_types=1);

use App\Core\Tenancy\Database\PostgresRlsHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Saldos de Estoque em Tempo Real por Depósito e Insumo/Produto.
     */
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('item_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->decimal('quantity_reserved', 15, 4)->default(0);
            $table->decimal('average_cost', 15, 4)->default(0); // Custo Médio Ponderado (CMP)
            $table->decimal('total_value', 15, 4)->default(0); // quantity_on_hand * average_cost
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'inventory_balances_tenant_id_id_unique');
            $table->unique(['tenant_id', 'item_id', 'warehouse_id'], 'inventory_balances_tenant_item_wh_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'item_id'])
                ->references(['tenant_id', 'id'])
                ->on('items')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'warehouse_id'])
                ->references(['tenant_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('inventory_balances');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('inventory_balances');
        Schema::dropIfExists('inventory_balances');
    }
};
