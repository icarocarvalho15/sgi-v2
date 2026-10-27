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
        Schema::create('partners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('partner_type', 20)->default('CUSTOMER'); // CUSTOMER, SUPPLIER, BOTH
            $table->string('person_type', 20)->default('LEGAL_ENTITY'); // LEGAL_ENTITY, INDIVIDUAL
            $table->string('document_number', 30); // CNPJ or CPF
            $table->string('state_registration', 30)->nullable();
            $table->string('name', 255); // Razao Social / Nome Completo
            $table->string('trade_name', 255)->nullable(); // Nome Fantasia
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'document_number']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('partners');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('partners');
        Schema::dropIfExists('partners');
    }
};
