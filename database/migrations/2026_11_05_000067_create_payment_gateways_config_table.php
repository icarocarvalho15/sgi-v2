<?php

declare(strict_types=1);

use App\Core\Tenancy\Database\PostgresRlsHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurações de Gateways de Pagamento por Tenant (Asaas, Mercado Pago, Stripe).
     */
    public function up(): void
    {
        Schema::create('payment_gateways_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('gateway_name', 30)->index(); // asaas, mercadopago, stripe
            $table->boolean('is_active')->default(false)->index();
            // Segredos de API criptografados via VaultEncryption
            $table->text('api_key_encrypted')->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            $table->string('mode', 20)->default('sandbox'); // sandbox, production
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique keys
            $table->unique(['tenant_id', 'id'], 'payment_gateways_config_tenant_id_id_unique');
            $table->unique(['tenant_id', 'gateway_name'], 'payment_gateways_config_tenant_gateway_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('payment_gateways_config');
    }

    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('payment_gateways_config');
        Schema::dropIfExists('payment_gateways_config');
    }
};
