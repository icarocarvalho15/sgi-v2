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
     * Razão Imutável de Kardex (Histórico detalhado de movimentação de estoque com CMP).
     */
    public function up(): void
    {
        Schema::create('kardex_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('item_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->string('movement_type', 30)->index(); // purchase_receipt, production_consumption, production_return, inventory_adjustment, transfer_in, transfer_out, sales_dispatch
            $table->string('direction', 10)->index(); // in, out
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_cost', 15, 4);
            $table->decimal('balance_quantity_after', 15, 4);
            $table->decimal('balance_average_cost_after', 15, 4);
            $table->decimal('balance_total_value_after', 15, 4);
            $table->string('reference_type', 50)->nullable()->index();
            $table->uuid('reference_id')->nullable()->index();
            $table->uuid('user_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'kardex_movements_tenant_id_id_unique');

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
        PostgresRlsHelper::applyRlsToTable('kardex_movements');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('kardex_movements');
        Schema::dropIfExists('kardex_movements');
    }
};
