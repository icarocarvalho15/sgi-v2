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
        Schema::create('kardex_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('movement_number', 50)->index();
            $table->uuid('warehouse_id');
            $table->uuid('item_id');

            // IN_PURCHASE, IN_PRODUCTION, IN_ADJUSTMENT, IN_TRANSFER, OUT_PRODUCTION, OUT_SALE, OUT_ADJUSTMENT, OUT_TRANSFER, OUT_SCRAP
            $table->string('movement_type', 30)->index();

            // Precisão industrial decimal(15, 4) - Proibido float
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->decimal('previous_balance', 15, 4);
            $table->decimal('resulting_balance', 15, 4);
            $table->decimal('previous_average_cost', 15, 4)->default(0);
            $table->decimal('resulting_average_cost', 15, 4)->default(0);

            // Rastreabilidade e Auditoria
            $table->string('reference_type')->nullable(); // ProductionOrder, PurchaseInvoice, etc.
            $table->uuid('reference_id')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->text('justification')->nullable(); // Obrigatório para ajustes manuais
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['tenant_id', 'id']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'warehouse_id'])
                ->references(['tenant_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'item_id'])
                ->references(['tenant_id', 'id'])
                ->on('items')
                ->restrictOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('kardex_movements');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('kardex_movements');
        Schema::dropIfExists('kardex_movements');
    }
};
