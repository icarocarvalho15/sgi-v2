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
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('quote_number', 50);
            $table->uuid('partner_id');
            $table->uuid('deal_id')->nullable();
            $table->string('status', 20)->default('DRAFT'); // DRAFT, SENT, APPROVED, REJECTED, CONVERTED
            $table->decimal('total_materials_cost', 15, 4)->default(0.0000);
            $table->decimal('total_operations_cost', 15, 4)->default(0.0000);
            $table->decimal('markup_percentage', 5, 2)->default(30.00);
            $table->decimal('margin_percentage', 5, 2)->default(23.08);
            $table->decimal('final_total', 15, 2)->default(0.00);
            $table->unsignedInteger('lead_time_days')->default(15);
            $table->date('valid_until')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'quote_number']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'deal_id'])
                ->references(['tenant_id', 'id'])
                ->on('crm_deals')
                ->nullOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('quotes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('quotes');
        Schema::dropIfExists('quotes');
    }
};
