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
        Schema::create('crm_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('pipeline_id');
            $table->uuid('stage_id');
            $table->uuid('partner_id');
            $table->string('title', 255);
            $table->decimal('estimated_value', 15, 2)->default(0.00);
            $table->date('expected_close_date')->nullable();
            $table->string('status', 20)->default('OPEN'); // OPEN, WON, LOST
            $table->string('priority', 20)->default('MEDIUM'); // LOW, MEDIUM, HIGH, URGENT
            $table->foreignUuid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'pipeline_id'])
                ->references(['tenant_id', 'id'])
                ->on('crm_pipelines')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'stage_id'])
                ->references(['tenant_id', 'id'])
                ->on('crm_deal_stages')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->restrictOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('crm_deals');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('crm_deals');
        Schema::dropIfExists('crm_deals');
    }
};
