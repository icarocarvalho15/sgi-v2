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
     * Unified partners table: Clientes, Fornecedores ou Ambos.
     */
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('type', 20)->default('customer'); // customer, supplier, both
            $table->string('person_type', 20)->default('company'); // company (PJ), individual (PF)
            $table->string('name'); // Razão Social / Nome Completo
            $table->string('trade_name')->nullable(); // Nome Fantasia
            $table->string('document_number', 20)->index(); // CNPJ ou CPF sem pontuação
            $table->string('state_registration', 30)->nullable(); // Inscrição Estadual
            $table->string('municipal_registration', 30)->nullable(); // Inscrição Municipal
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->boolean('is_tax_exempt')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key for downstream composite FKs
            $table->unique(['tenant_id', 'id'], 'partners_tenant_id_id_unique');

            // Unique document per tenant
            $table->unique(['tenant_id', 'document_number'], 'partners_tenant_document_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('partners');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('partners');
        Schema::dropIfExists('partners');
    }
};
