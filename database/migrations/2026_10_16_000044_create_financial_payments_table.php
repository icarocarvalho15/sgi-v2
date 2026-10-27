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
        Schema::create('financial_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('financial_entry_id');
            $table->uuid('financial_account_id');
            $table->string('payment_method', 30)->default('PIX'); // PIX, BOLETO, CREDIT_CARD, BANK_TRANSFER, CASH
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('interest_paid', 15, 2)->default(0.00);
            $table->decimal('fine_paid', 15, 2)->default(0.00);
            $table->decimal('discount_applied', 15, 2)->default(0.00);
            $table->decimal('net_amount', 15, 2);
            $table->boolean('is_reversed')->default(false);
            $table->text('reversal_reason')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'financial_entry_id'])
                ->references(['tenant_id', 'id'])
                ->on('financial_entries')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'financial_account_id'])
                ->references(['tenant_id', 'id'])
                ->on('financial_accounts')
                ->restrictOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('financial_payments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('financial_payments');
        Schema::dropIfExists('financial_payments');
    }
};
