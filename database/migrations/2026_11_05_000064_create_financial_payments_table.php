<?php

declare(strict_types=1);

use App\Core\Tenancy\Database\PostgresRlsHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baixas de Títulos Financeiros (Pagamentos e Recebimentos Parciais ou Totais).
     */
    public function up(): void
    {
        Schema::create('financial_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('financial_entry_id')->index();
            $table->uuid('bank_account_id')->index();
            $table->string('payment_method', 30); // pix, boleto, credit_card, bank_transfer, cash
            $table->decimal('amount', 15, 4);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('interest_fine_amount', 15, 4)->default(0);
            $table->date('payment_date')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('external_transaction_id', 100)->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique keys
            $table->unique(['tenant_id', 'id'], 'financial_payments_tenant_id_id_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'financial_entry_id'])
                ->references(['tenant_id', 'id'])
                ->on('financial_entries')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'bank_account_id'])
                ->references(['tenant_id', 'id'])
                ->on('bank_accounts')
                ->restrictOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('financial_payments');
    }

    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('financial_payments');
        Schema::dropIfExists('financial_payments');
    }
};
