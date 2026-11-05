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
     * Categorias de Insumos e Produtos (Papéis, Chapas CTP, Tintas, Brindes Brutos, Embalagens).
     */
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('code', 30)->index();
            $table->string('type', 30)->default('raw_material'); // raw_material, intermediate, finished_good, supply
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'item_categories_tenant_id_id_unique');
            $table->unique(['tenant_id', 'code'], 'item_categories_tenant_code_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('item_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('item_categories');
        Schema::dropIfExists('item_categories');
    }
};
