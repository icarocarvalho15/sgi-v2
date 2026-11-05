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
     * Fichas Técnicas (BOM - Bill of Materials) para Engenharia de Produtos Gráficos e Brindes.
     */
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('code', 50)->index();
            $table->string('name');
            $table->string('product_type', 40)->default('commercial_print'); // commercial_print, custom_gift, packaging, sub_assembly
            $table->integer('version')->default(1);
            $table->uuid('parent_bom_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'boms_tenant_id_id_unique');
            $table->unique(['tenant_id', 'code'], 'boms_tenant_code_unique');

            // Double Lock Composite FK to self (for sub-assemblies)
            $table->foreign(['tenant_id', 'parent_bom_id'])
                ->references(['tenant_id', 'id'])
                ->on('boms')
                ->restrictOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('boms');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('boms');
        Schema::dropIfExists('boms');
    }
};
