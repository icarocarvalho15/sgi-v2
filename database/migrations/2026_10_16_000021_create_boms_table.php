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
        Schema::create('boms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('item_id');
            $table->string('code', 50);
            $table->unsignedInteger('version')->default(1);
            $table->text('description')->nullable();
            $table->decimal('batch_size', 15, 4)->default(1.0000);
            $table->decimal('total_estimated_cost', 15, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'item_id', 'version']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'item_id'])
                ->references(['tenant_id', 'id'])
                ->on('items')
                ->restrictOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('boms');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('boms');
        Schema::dropIfExists('boms');
    }
};
