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
     * Depósitos / Almoxarifados (Almoxarifado Geral, Produção / Chão de Fábrica, Quarentena, Expedição).
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('code', 30)->index();
            $table->string('name');
            $table->string('location')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'warehouses_tenant_id_id_unique');
            $table->unique(['tenant_id', 'code'], 'warehouses_tenant_code_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('warehouses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('warehouses');
        Schema::dropIfExists('warehouses');
    }
};
