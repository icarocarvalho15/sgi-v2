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
        Schema::create('production_order_operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('production_order_id');
            $table->uuid('routing_step_id')->nullable();
            $table->uuid('work_center_id');
            $table->unsignedInteger('sequence')->default(1);
            $table->string('status', 30)->default('PENDING');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('executed_time_seconds')->default(0);
            $table->uuid('operator_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'production_order_id'])
                ->references(['tenant_id', 'id'])
                ->on('production_orders')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'work_center_id'])
                ->references(['tenant_id', 'id'])
                ->on('work_centers')
                ->restrictOnDelete();

            $table->foreign('operator_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('production_order_operations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('production_order_operations');
        Schema::dropIfExists('production_order_operations');
    }
};
