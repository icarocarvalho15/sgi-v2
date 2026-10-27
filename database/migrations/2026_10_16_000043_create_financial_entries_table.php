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
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('entry_type', 20); // RECEIVABLE, PAYABLE
            $table->uuid('partner_id')->nullable();
            $table->uuid('chart_of_account_id');
            $table->uuid('cost_center_id')->nullable();
            $table->uuid('sales_order_id')->nullable();
            $table->string('document_number', 50);
            $table->string('title', 255);
            $table->unsignedInteger('installment_number')->default(1);
            $table->unsignedInteger('total_installments')->default(1);
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('competence_date');
            $table->decimal('original_amount', 15, 2);
            $table->decimal('balance_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('interest_amount', 15, 2)->default(0.00);
            $table->decimal('fine_amount', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->string('status', 20)->default('PENDING'); // PENDING, PARTIALLY_PAID, PAID, CANCELLED
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->nullOnDelete();

            $table->foreign(['tenant_id', 'chart_of_account_id'])
                ->references(['tenant_id', 'id'])
                ->on('chart_of_accounts')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'cost_center_id'])
                ->references(['tenant_id', 'id'])
                ->on('cost_centers')
                ->nullOnDelete();

            $table->foreign(['tenant_id', 'sales_order_id'])
                ->references(['tenant_id', 'id'])
                ->on('sales_orders')
                ->nullOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('financial_entries');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('financial_entries');
        Schema::dropIfExists('financial_entries');
    }
};
