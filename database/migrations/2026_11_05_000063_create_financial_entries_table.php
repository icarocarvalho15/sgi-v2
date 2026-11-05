<?php

declare(strict_types=1);

use App\Core\Tenancy\Database\PostgresRlsHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Títulos Financeiros: Contas a Receber e Contas a Pagar.
     */
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('type', 20)->index(); // receivable, payable
            $table->string('entry_number', 50)->index();
            $table->uuid('partner_id')->nullable()->index();
            $table->uuid('chart_of_account_id')->nullable()->index();
            $table->uuid('cost_center_id')->nullable()->index();
            $table->uuid('bank_account_id')->nullable()->index();
            $table->string('status', 20)->default('pending')->index(); // pending, partial, paid, cancelled
            $table->string('description');
            $table->decimal('original_amount', 15, 4);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('interest_fine_amount', 15, 4)->default(0);
            $table->decimal('net_amount', 15, 4);
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4);
            $table->date('issue_date')->index();
            $table->date('due_date')->index();
            $table->date('payment_date')->nullable()->index();
            $table->date('competence_date')->index();
            $table->unsignedSmallInteger('installment_number')->default(1);
            $table->unsignedSmallInteger('total_installments')->default(1);
            $table->string('reference_type', 50)->nullable()->index();
            $table->uuid('reference_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique keys
            $table->unique(['tenant_id', 'id'], 'financial_entries_tenant_id_id_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->nullOnDelete();

            $table->foreign(['tenant_id', 'chart_of_account_id'])
                ->references(['tenant_id', 'id'])
                ->on('chart_of_accounts')
                ->nullOnDelete();

            $table->foreign(['tenant_id', 'cost_center_id'])
                ->references(['tenant_id', 'id'])
                ->on('cost_centers')
                ->nullOnDelete();

            $table->foreign(['tenant_id', 'bank_account_id'])
                ->references(['tenant_id', 'id'])
                ->on('bank_accounts')
                ->nullOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('financial_entries');
    }

    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('financial_entries');
        Schema::dropIfExists('financial_entries');
    }
};
