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
        Schema::create('fiscal_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('sales_order_id')->nullable();
            $table->uuid('partner_id');
            $table->string('invoice_type', 10)->default('NFE'); // NFE, NFSE
            $table->unsignedInteger('invoice_number');
            $table->unsignedInteger('series')->default(1);
            $table->string('environment', 20)->default('HOMOLOGATION'); // HOMOLOGATION, PRODUCTION
            $table->string('access_key', 44)->nullable()->index();
            $table->string('protocol_number', 50)->nullable();
            $table->string('status', 30)->default('DRAFT'); // DRAFT, PROCESSING, AUTHORIZED, REJECTED, CANCELLED
            $table->decimal('total_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'invoice_type', 'series', 'invoice_number']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'sales_order_id'])
                ->references(['tenant_id', 'id'])
                ->on('sales_orders')
                ->nullOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('fiscal_invoices');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('fiscal_invoices');
        Schema::dropIfExists('fiscal_invoices');
    }
};
