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
     * Funil de Vendas / Oportunidades (Kanban) integrado aos status customizáveis do tenant.
     */
    public function up(): void
    {
        Schema::create('crm_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('partner_id'); // Cliente potencial
            $table->uuid('status_id');  // Referência a tenant_statuses (module = 'sales_kanban')
            $table->string('title');
            $table->decimal('estimated_value', 15, 4)->default(0);
            $table->integer('probability_percent')->default(50);
            $table->date('expected_close_date')->nullable();
            $table->uuid('assigned_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'crm_deals_tenant_id_id_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'partner_id'])
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
        PostgresRlsHelper::applyRlsToTable('crm_deals');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('crm_deals');
        Schema::dropIfExists('crm_deals');
    }
};
