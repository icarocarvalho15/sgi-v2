<?php

declare(strict_types=1);

use App\Core\Tenancy\Database\PostgresRlsHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plano de Contas Gerencial e Contábil (Receitas, Despesas Operacionais, Custos Gráficos).
     */
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('code', 30)->index(); // Ex: 1.01.001
            $table->string('name');
            $table->string('type', 20); // revenue, expense, cost_of_goods, asset, liability
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique keys
            $table->unique(['tenant_id', 'id'], 'chart_of_accounts_tenant_id_id_unique');
            $table->unique(['tenant_id', 'code'], 'chart_of_accounts_tenant_code_unique');

            // Composite FK self-referencing for hierarchy
            $table->foreign(['tenant_id', 'parent_id'])
                ->references(['tenant_id', 'id'])
                ->on('chart_of_accounts')
                ->nullOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('chart_of_accounts');
    }

    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('chart_of_accounts');
        Schema::dropIfExists('chart_of_accounts');
    }
};
