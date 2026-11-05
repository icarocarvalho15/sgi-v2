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
     * Tabela central de Orçamentos Técnicos para Gráficas e Brindes Personalizados.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('quote_number', 50)->index();
            $table->uuid('customer_id');
            $table->uuid('status_id'); // Referência dinâmica à tabela tenant_statuses (module = 'quotes')
            $table->string('title');
            $table->text('notes')->nullable();
            
            // Precisão Zero Float (NUMERIC 15,4)
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('freight_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('estimated_cost', 15, 4)->default(0);
            $table->decimal('target_margin_percent', 8, 4)->default(0);

            $table->integer('production_lead_time_days')->default(5);
            $table->date('valid_until')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'quotes_tenant_id_id_unique');
            $table->unique(['tenant_id', 'quote_number'], 'quotes_tenant_number_unique');

            // Double Lock Composite Foreign Keys
            $table->foreign(['tenant_id', 'customer_id'])
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
        PostgresRlsHelper::applyRlsToTable('quotes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('quotes');
        Schema::dropIfExists('quotes');
    }
};
