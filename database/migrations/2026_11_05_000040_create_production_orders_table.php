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
     * Ordens de Produção (OPs) para acompanhamento no chão de fábrica da gráfica / indústria de brindes.
     */
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('order_number', 50)->index(); // ex: OP-2026-0001
            $table->uuid('sales_order_id')->nullable();
            $table->uuid('quote_item_id')->nullable();
            $table->uuid('status_id'); // tenant_statuses (module = 'production_orders')
            $table->string('product_name');
            $table->integer('target_quantity');
            $table->integer('produced_quantity')->default(0);
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->jsonb('technical_notes')->nullable(); // Cores, Chapa CTP, Gramatura, Processo, etc.
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'production_orders_tenant_id_id_unique');
            $table->unique(['tenant_id', 'order_number'], 'production_orders_tenant_number_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'status_id'])
                ->references(['tenant_id', 'id'])
                ->on('tenant_statuses')
                ->restrictOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('production_orders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('production_orders');
        Schema::dropIfExists('production_orders');
    }
};
