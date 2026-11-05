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
     * Itens técnicos de Orçamento: especificações gráficas, chapas CTP, processos de brinde e acabamentos.
     */
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('quote_id');
            $table->string('product_type', 30)->default('commercial_print'); // commercial_print, custom_gift
            $table->string('name');
            $table->integer('quantity'); // Tiragem solicitada
            
            // Especificações Gráficas Técnicas
            $table->string('format_open', 50)->nullable(); // ex: "420x297 mm"
            $table->string('format_closed', 50)->nullable(); // ex: "210x297 mm"
            $table->integer('pages_count')->default(1);
            $table->string('substrate_type')->nullable(); // ex: "Papel Couché Fosco 150g"
            $table->integer('colors_front')->default(4); // ex: 4 cores
            $table->integer('colors_back')->default(0);  // ex: 0 cores
            $table->integer('ctp_plates_count')->default(4); // Número de chapas CTP calculadas
            $table->string('customization_process')->nullable(); // ex: "Gravação Laser Fibra", "Silk Screen"
            $table->jsonb('finishing_operations')->nullable(); // ex: ["Guilhotina", "Dobra", "Laminação BOPP"]

            // Precificação Zero Float (NUMERIC 15,4)
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('markup_multiplier', 8, 4)->default(1.5000); // 1.5 = 50% margem
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('total_price', 15, 4)->default(0);

            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'quote_items_tenant_id_id_unique');

            // Double Lock Composite FK
            $table->foreign(['tenant_id', 'quote_id'])
                ->references(['tenant_id', 'id'])
                ->on('quotes')
                ->cascadeOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('quote_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('quote_items');
        Schema::dropIfExists('quote_items');
    }
};
