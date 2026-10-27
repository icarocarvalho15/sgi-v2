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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('order_number', 50);
            $table->uuid('partner_id');
            $table->uuid('quote_id')->nullable();
            $table->uuid('deal_id')->nullable();
            $table->string('status', 20)->default('CONFIRMED'); // CONFIRMED, IN_PRODUCTION, SHIPPED, COMPLETED, CANCELLED
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->date('delivery_date')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'order_number']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'quote_id'])
                ->references(['tenant_id', 'id'])
                ->on('quotes')
                ->nullOnDelete();

            $table->foreign(['tenant_id', 'deal_id'])
                ->references(['tenant_id', 'id'])
                ->on('crm_deals')
                ->nullOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('sales_orders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('sales_orders');
        Schema::dropIfExists('sales_orders');
    }
};
