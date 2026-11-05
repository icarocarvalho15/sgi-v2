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
     * Pedidos de Venda gerados pela conversão em 1-clique ou venda direta.
     */
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('order_number', 50)->index();
            $table->uuid('quote_id')->nullable();
            $table->uuid('customer_id');
            $table->uuid('status_id'); // tenant_statuses (module = 'sales_orders' ou 'sales_kanban')
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->date('delivery_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'sales_orders_tenant_id_id_unique');
            $table->unique(['tenant_id', 'order_number'], 'sales_orders_tenant_number_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'customer_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->restrictOnDelete();

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
        PostgresRlsHelper::applyRlsToTable('sales_orders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('sales_orders');
        Schema::dropIfExists('sales_orders');
    }
};
