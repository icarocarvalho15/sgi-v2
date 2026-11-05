<?php

declare(strict_types=1);

use App\Core\Tenancy\Database\PostgresRlsHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notas Fiscais Eletrônicas (NFe de mercadorias / NFSe de serviços gráficos).
     */
    public function up(): void
    {
        Schema::create('fiscal_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('type', 10)->default('nfe')->index(); // nfe, nfse
            $table->uuid('sales_order_id')->nullable()->index();
            $table->uuid('partner_id')->nullable()->index();
            $table->string('status', 20)->default('draft')->index(); // draft, processing, authorized, rejected, cancelled
            $table->unsignedInteger('invoice_number')->nullable()->index();
            $table->unsignedSmallInteger('series')->default(1);
            $table->string('access_key', 44)->nullable()->index(); // Chave de acesso 44 dígitos
            $table->string('protocol_number', 50)->nullable();
            $table->string('xml_path')->nullable();
            $table->string('pdf_danfe_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->jsonb('items_data')->nullable();
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique keys
            $table->unique(['tenant_id', 'id'], 'fiscal_invoices_tenant_id_id_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'sales_order_id'])
                ->references(['tenant_id', 'id'])
                ->on('sales_orders')
                ->nullOnDelete();

            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->nullOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('fiscal_invoices');
    }

    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('fiscal_invoices');
        Schema::dropIfExists('fiscal_invoices');
    }
};
