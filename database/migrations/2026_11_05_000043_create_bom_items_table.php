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
     * Insumos e Componentes da Ficha Técnica (Papéis, Chapas CTP, Tintas, Embalagens, etc.).
     */
    public function up(): void
    {
        Schema::create('bom_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('bom_id');
            $table->string('item_type', 30); // substrate, ctp_plate, ink, finishing_supply, sub_assembly
            $table->string('name');
            $table->uuid('child_bom_id')->nullable(); // Para subconjuntos
            $table->decimal('quantity', 15, 4)->default(1);
            $table->string('unit_of_measure', 10)->default('UN'); // UN, FL, KG, M2
            $table->decimal('scrap_factor_percent', 8, 4)->default(0); // Quebra/setup técnico
            $table->uuid('work_center_id')->nullable(); // Máquina onde o insumo é aplicado
            $table->text('notes')->nullable();
            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'bom_items_tenant_id_id_unique');

            // Double Lock Composite Foreign Keys
            $table->foreign(['tenant_id', 'bom_id'])
                ->references(['tenant_id', 'id'])
                ->on('boms')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'child_bom_id'])
                ->references(['tenant_id', 'id'])
                ->on('boms')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'work_center_id'])
                ->references(['tenant_id', 'id'])
                ->on('work_centers')
                ->nullOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('bom_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('bom_items');
        Schema::dropIfExists('bom_items');
    }
};
