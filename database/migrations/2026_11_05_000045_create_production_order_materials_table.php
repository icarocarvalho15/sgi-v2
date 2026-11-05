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
     * Lista de Materiais e Insumos Empenhados para cada Ordem de Produção (com quebra técnica).
     */
    public function up(): void
    {
        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('production_order_id');
            $table->uuid('bom_item_id')->nullable();
            $table->string('name');
            $table->string('item_type', 30); // substrate, ctp_plate, ink, finishing_supply
            $table->decimal('planned_quantity', 15, 4); // Quantidade planejada (tiragem * consumo + quebra)
            $table->decimal('consumed_quantity', 15, 4)->default(0); // Real consumido no chão de fábrica
            $table->string('unit_of_measure', 10)->default('UN');
            $table->string('status', 20)->default('reserved'); // reserved, consumed, returned
            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'prod_order_materials_tenant_id_id_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'production_order_id'])
                ->references(['tenant_id', 'id'])
                ->on('production_orders')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'bom_item_id'])
                ->references(['tenant_id', 'id'])
                ->on('bom_items')
                ->nullOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('production_order_materials');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('production_order_materials');
        Schema::dropIfExists('production_order_materials');
    }
};
