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
     * Centros de Trabalho e Máquinas (CTP, Impressoras Offset, Plotters Digitais, Guilhotinas, Lasers de Brindes).
     */
    public function up(): void
    {
        Schema::create('work_centers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('code', 30)->index();
            $table->string('type', 40); // pre_press_ctp, offset_printing, digital_printing, finishing, custom_gifts_laser, silk_screen
            $table->decimal('hourly_rate', 15, 4)->default(0);
            $table->decimal('capacity_per_hour', 15, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique key
            $table->unique(['tenant_id', 'id'], 'work_centers_tenant_id_id_unique');
            $table->unique(['tenant_id', 'code'], 'work_centers_tenant_code_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        // Double Lock Requirement 1.2: Native PostgreSQL Row-Level Security
        PostgresRlsHelper::applyRlsToTable('work_centers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::dropRlsFromTable('work_centers');
        Schema::dropIfExists('work_centers');
    }
};
