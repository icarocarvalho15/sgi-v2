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
     * Múltiplos endereços para Clientes e Fornecedores (Cobrana, Entrega, Matriz, Filial).
     */
    public function up(): void
    {
        Schema::create('partner_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('partner_id');
            $table->string('type', 20)->default('main'); // main, billing, shipping
            $table->string('postal_code', 10); // CEP
            $table->string('street');
            $table->string('number', 20);
            $table->string('complement')->nullable();
            $table->string('neighborhood'); // Bairro
            $table->string('city');
            $table->string('state', 2); // UF (ex: SP, RJ, MG)
            $table->string('ibge_code', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'partner_addresses_tenant_id_id_unique');

            // Double Lock Composite Foreign Key referencing (tenant_id, id) of parent table
            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->cascadeOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('partner_addresses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('partner_addresses');
        Schema::dropIfExists('partner_addresses');
    }
};
