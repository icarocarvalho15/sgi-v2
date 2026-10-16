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
        Schema::create('routing_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('bom_id');
            $table->uuid('work_center_id');
            $table->unsignedInteger('sequence')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('setup_time_minutes', 10, 2)->default(0.00);
            $table->decimal('run_time_minutes_per_unit', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'bom_id', 'sequence']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'bom_id'])
                ->references(['tenant_id', 'id'])
                ->on('boms')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'work_center_id'])
                ->references(['tenant_id', 'id'])
                ->on('work_centers')
                ->restrictOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('routing_steps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('routing_steps');
        Schema::dropIfExists('routing_steps');
    }
};
