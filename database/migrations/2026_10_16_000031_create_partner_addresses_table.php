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
        Schema::create('partner_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('partner_id');
            $table->string('address_type', 20)->default('MAIN'); // BILLING, SHIPPING, MAIN
            $table->string('postal_code', 20); // CEP
            $table->string('street', 255);
            $table->string('number', 50);
            $table->string('complement', 150)->nullable();
            $table->string('district', 100);
            $table->string('city', 100);
            $table->string('state', 2);
            $table->string('country', 3)->default('BR');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'partner_id'])
                ->references(['tenant_id', 'id'])
                ->on('partners')
                ->cascadeOnDelete();
        });

        PostgresRlsHelper::enableTenantRls('partner_addresses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PostgresRlsHelper::disableTenantRls('partner_addresses');
        Schema::dropIfExists('partner_addresses');
    }
};
