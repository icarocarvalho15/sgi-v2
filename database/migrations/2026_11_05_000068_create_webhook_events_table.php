<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela Global de Idempotência e Auditoria de Webhooks (Gateways & Fiscal).
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 30)->index(); // asaas, mercadopago, stripe, fiscal
            $table->string('external_event_id', 150)->index();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('event_type', 100)->nullable()->index();
            $table->string('status', 20)->default('pending')->index(); // pending, processed, failed, ignored
            $table->jsonb('payload');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            // Idempotência estrita: um evento externo jamais é processado duas vezes
            $table->unique(['provider', 'external_event_id'], 'webhook_events_provider_external_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
