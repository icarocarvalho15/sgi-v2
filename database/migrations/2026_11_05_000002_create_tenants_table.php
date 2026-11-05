<?php

declare(strict_types=1);

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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plan_id')->nullable();
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->string('document_number', 20)->nullable()->index(); // CNPJ
            $table->string('subdomain')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('status')->default('active')->index(); // active, suspended, cancelled
            $table->timestamp('trial_ends_at')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('plan_id')
                ->references('id')
                ->on('plans')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
