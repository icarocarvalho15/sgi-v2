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
        Schema::create('crm_deal_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('pipeline_id');
            $table->string('name', 100);
            $table->unsignedInteger('sequence')->default(1);
            $table->decimal('probability_percentage', 5, 2)->default(0.00);
            $table->string('color', 30)->default('#6366f1');
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'pipeline_id', 'sequence']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'pipeline_id'])
                ->references(['tenant_id', 'id'])
                ->on('crm_pipelines')
                ->cascadeOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('crm_deal_stages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('crm_deal_stages');
        Schema::dropIfExists('crm_deal_stages');
    }
};
