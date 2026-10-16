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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('order_number', 50);
            $table->uuid('item_id');
            $table->uuid('bom_id');
            $table->uuid('warehouse_id'); // Destino do Produto Acabado
            $table->uuid('materials_warehouse_id'); // Origem dos Insumos
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('produced_quantity', 15, 4)->default(0.0000);
            $table->decimal('scrapped_quantity', 15, 4)->default(0.0000);
            $table->string('status', 30)->default('DRAFT');
            $table->string('priority', 20)->default('MEDIUM');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'order_number']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'item_id'])
                ->references(['tenant_id', 'id'])
                ->on('items')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'bom_id'])
                ->references(['tenant_id', 'id'])
                ->on('boms')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'warehouse_id'])
                ->references(['tenant_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'materials_warehouse_id'])
                ->references(['tenant_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('production_orders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('production_orders');
        Schema::dropIfExists('production_orders');
    }
};
