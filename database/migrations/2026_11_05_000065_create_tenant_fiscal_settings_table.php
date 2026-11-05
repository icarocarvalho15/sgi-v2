<?php

declare(strict_types=1);

use App\Core\Tenancy\Database\PostgresRlsHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurações Fiscais, Certificado Digital A1 e Parâmetros Tributários do Tenant.
     */
    public function up(): void
    {
        Schema::create('tenant_fiscal_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('cnpj', 20)->index();
            $table->string('state_registration', 30)->nullable();
            $table->string('municipal_registration', 30)->nullable();
            $table->string('tax_regime', 30)->default('simples_nacional'); // simples_nacional, lucro_presumido, lucro_real
            $table->string('environment', 20)->default('sandbox'); // sandbox, production
            $table->unsignedInteger('next_nfe_number')->default(1);
            $table->unsignedSmallInteger('nfe_series')->default(1);
            $table->unsignedInteger('next_nfse_number')->default(1);
            $table->unsignedSmallInteger('nfse_series')->default(1);
            $table->string('default_ncm', 20)->nullable();
            $table->string('default_cfop', 10)->nullable();
            // Dados sensíveis protegidos por VaultEncryption (AES-256-GCM + HKDF)
            $table->text('certificate_a1_encrypted')->nullable();
            $table->text('certificate_password_encrypted')->nullable();
            $table->timestamp('certificate_expires_at')->nullable();
            $table->string('fiscal_provider', 30)->default('focus_nfe');
            $table->text('fiscal_token_encrypted')->nullable();
            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique keys
            $table->unique(['tenant_id', 'id'], 'tenant_fiscal_settings_tenant_id_id_unique');
            $table->unique('tenant_id', 'tenant_fiscal_settings_tenant_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('tenant_fiscal_settings');
    }

    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('tenant_fiscal_settings');
        Schema::dropIfExists('tenant_fiscal_settings');
    }
};
