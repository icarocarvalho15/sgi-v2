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
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('code', 10);
            $table->string('symbol', 10);
            $table->string('type', 20)->default('unit'); // unit, weight, volume, length, area
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Chave única composta para relacionamento Multi-Tenant
            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'code']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('units_of_measure');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('units_of_measure');
        Schema::dropIfExists('units_of_measure');
    }
};
