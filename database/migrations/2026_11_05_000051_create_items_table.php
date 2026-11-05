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
     * Insumos e Produtos Gráficos e de Brindes (Papéis, Chapas CTP, Tintas, Brindes Brutos).
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('category_id');
            $table->string('code', 50)->index(); // SKU
            $table->string('name');
            $table->string('type', 30)->default('raw_material'); // raw_material, wip, finished_good, packaging, supply
            $table->string('unit_of_measure', 10)->default('UN'); // FL, KG, UN, M2, LT, CX
            $table->decimal('min_stock', 15, 4)->default(0);
            $table->decimal('max_stock', 15, 4)->default(0);
            $table->decimal('standard_cost', 15, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('specifications')->nullable(); // Gramatura, formato folha, fabricante, cor
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'items_tenant_id_id_unique');
            $table->unique(['tenant_id', 'code'], 'items_tenant_code_unique');

            // Double Lock Composite FK
            $table->foreign(['tenant_id', 'category_id'])
                ->references(['tenant_id', 'id'])
                ->on('item_categories')
                ->restrictOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('items');
        Schema::dropIfExists('items');
    }
};
