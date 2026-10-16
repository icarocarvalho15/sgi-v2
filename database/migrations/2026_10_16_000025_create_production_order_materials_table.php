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
        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('production_order_id');
            $table->uuid('item_id');
            $table->uuid('unit_of_measure_id');
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('consumed_quantity', 15, 4)->default(0.0000);
            $table->decimal('unit_cost', 15, 4)->default(0.0000);
            $table->decimal('total_cost', 15, 4)->default(0.0000);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'production_order_id'])
                ->references(['tenant_id', 'id'])
                ->on('production_orders')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'item_id'])
                ->references(['tenant_id', 'id'])
                ->on('items')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'unit_of_measure_id'])
                ->references(['tenant_id', 'id'])
                ->on('units_of_measure')
                ->restrictOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('production_order_materials');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('production_order_materials');
        Schema::dropIfExists('production_order_materials');
    }
};
