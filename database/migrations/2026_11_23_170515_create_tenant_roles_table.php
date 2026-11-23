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
        Schema::create('tenant_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#6366F1');
            $table->jsonb('permissions')->default('[]');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Double Lock Requirement 1.1: Composite unique keys
            $table->unique(['tenant_id', 'id'], 'tenant_roles_tenant_id_id_unique');
            $table->unique(['tenant_id', 'slug'], 'tenant_roles_tenant_id_slug_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });

        // Double Lock Requirement 1.2: Row-Level Security
        PostgresRlsHelper::applyRlsToTable('tenant_roles');

        // Add role_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('role_id')->nullable()->after('role')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role_id']);
            $table->dropColumn('role_id');
        });

        PostgresRlsHelper::dropRlsFromTable('tenant_roles');
        Schema::dropIfExists('tenant_roles');
    }
};
