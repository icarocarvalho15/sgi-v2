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
     * Implements Double-Lock architecture:
     * 1. Foreign Key with tenant_id + composite unique key (tenant_id, id).
     * 2. PostgreSQL Row-Level Security (RLS) enabled and forced.
     */
    public function up(): void
    {
        Schema::create('tenant_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('module', 50)->index(); // quotes, production_orders, sales_kanban, crm_deals
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('color', 25)->default('#64748B');
            $table->integer('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_final')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique key for downstream composite FKs
            $table->unique(['tenant_id', 'id'], 'tenant_statuses_tenant_id_id_unique');

            // Unique slug per tenant and module
            $table->unique(['tenant_id', 'module', 'slug'], 'tenant_statuses_tenant_module_slug_unique');

            // Restrict on delete to avoid accidental orphaned history
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Native PostgreSQL Row-Level Security (RLS)
        PostgresRlsHelper::applyRlsToTable('tenant_statuses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('tenant_statuses');
        Schema::dropIfExists('tenant_statuses');
    }
};
