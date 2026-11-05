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
     * Apontamento de Chão de Fábrica: início, fim, operador, horas de máquina, quantidade boa e refugo técnico.
     */
    public function up(): void
    {
        Schema::create('production_time_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('production_order_id');
            $table->uuid('work_center_id');
            $table->uuid('user_id')->nullable(); // Operador responsável
            $table->string('operation_name'); // ex: "Acerto Heidelberg", "Tiragem Offset", "Corte Guilhotina", "Gravação Laser"
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('good_quantity')->default(0);  // Produzido aprovado
            $table->integer('scrap_quantity')->default(0); // Folhas de acerto / refugo
            $table->text('notes')->nullable();
            $table->timestamps();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'prod_time_logs_tenant_id_id_unique');

            // Double Lock Composite FKs
            $table->foreign(['tenant_id', 'production_order_id'])
                ->references(['tenant_id', 'id'])
                ->on('production_orders')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'work_center_id'])
                ->references(['tenant_id', 'id'])
                ->on('work_centers')
                ->restrictOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('production_time_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('production_time_logs');
        Schema::dropIfExists('production_time_logs');
    }
};
