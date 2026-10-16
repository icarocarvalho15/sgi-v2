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
        Schema::create('bom_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('bom_id');
            $table->uuid('item_id');
            $table->uuid('unit_of_measure_id');
            $table->decimal('quantity', 15, 4);
            $table->decimal('scrap_rate_percentage', 5, 2)->default(0.00);
            $table->decimal('unit_cost', 15, 4)->default(0.0000);
            $table->decimal('total_cost', 15, 4)->default(0.0000);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'bom_id'])
                ->references(['tenant_id', 'id'])
                ->on('boms')
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

        PostgresRlsHelper::enableTenantRls('bom_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('bom_items');
        Schema::dropIfExists('bom_items');
    }
};
