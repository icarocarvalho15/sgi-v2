<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Central Landlord table for auditing support impersonation into tenant accounts.
     */
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('impersonator_id'); // SuperAdmin user ID
            $table->uuid('tenant_id');       // Impersonated Tenant ID
            $table->uuid('impersonated_user_id')->nullable(); // Target user if specific
            $table->string('reason');         // Mandatory ticket or audit justification
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('impersonator_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
