<?php

declare(strict_types=1);

use App\Core\Tenancy\Database\PostgresRlsHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contas Bancárias e Caixas Físicos da Indústria Gráfica.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name'); // Ex: Banco Itaú PJ, Caixa Gaveta Recepção
            $table->string('bank_code', 10)->nullable(); // 341, 001, etc.
            $table->string('agency', 20)->nullable();
            $table->string('account_number', 30)->nullable();
            $table->string('type', 20)->default('checking'); // checking, savings, cash, gateway
            $table->decimal('initial_balance', 15, 4)->default(0);
            $table->decimal('current_balance', 15, 4)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique keys
            $table->unique(['tenant_id', 'id'], 'bank_accounts_tenant_id_id_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('bank_accounts');
    }

    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('bank_accounts');
        Schema::dropIfExists('bank_accounts');
    }
};
