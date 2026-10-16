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
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('sku', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('unit_of_measure_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('default_warehouse_id')->nullable();

            // Flags industriais de tipo de item
            $table->boolean('is_raw_material')->default(false);
            $table->boolean('is_manufactured')->default(false);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_service')->default(false);
            $table->boolean('allow_negative_stock')->default(false);

            // Parâmetros numéricos industriais de alta precisão (sem float)
            $table->decimal('min_stock', 15, 4)->default(0);
            $table->decimal('max_stock', 15, 4)->nullable();
            $table->decimal('average_cost', 15, 4)->default(0); // Custo Médio Ponderado (CMP)
            $table->decimal('last_cost', 15, 4)->default(0);    // Último custo de aquisição
            $table->decimal('sale_price', 15, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'sku']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'unit_of_measure_id'])
                ->references(['tenant_id', 'id'])
                ->on('units_of_measure')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'category_id'])
                ->references(['tenant_id', 'id'])
                ->on('item_categories')
                ->nullOnDelete();

            $table->foreign(['tenant_id', 'default_warehouse_id'])
                ->references(['tenant_id', 'id'])
                ->on('warehouses')
                ->nullOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('items');
        Schema::dropIfExists('items');
    }
};
