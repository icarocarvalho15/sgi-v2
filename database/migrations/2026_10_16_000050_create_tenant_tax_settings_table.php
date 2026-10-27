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
        Schema::create('tenant_tax_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('certificate_path')->nullable();
            $table->text('certificate_password')->nullable(); // Criptografado via VaultEncryption
            $table->timestamp('certificate_expires_at')->nullable();
            $table->string('environment', 20)->default('HOMOLOGATION'); // HOMOLOGATION, PRODUCTION
            $table->string('tax_regime', 30)->default('SIMPLES_NACIONAL'); // SIMPLES_NACIONAL, LUCRO_PRESUMIDO, LUCRO_REAL
            $table->string('state_registration', 30)->nullable();
            $table->string('municipal_registration', 30)->nullable();
            $table->unsignedInteger('nfe_series')->default(1);
            $table->unsignedInteger('next_nfe_number')->default(1);
            $table->string('default_cfop', 10)->default('5101');
            $table->string('default_csosn', 10)->nullable()->default('102');
            $table->string('default_cst', 10)->nullable();
            $table->decimal('default_iss_rate', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique('tenant_id');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('tenant_tax_settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('tenant_tax_settings');
        Schema::dropIfExists('tenant_tax_settings');
    }
};
