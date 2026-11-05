<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Vincula a Ordem de Produção à Ficha Técnica (BOM) correspondente via FK Composta.
     */
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->uuid('bom_id')->nullable()->after('quote_item_id');

            // Double Lock Composite FK
            $table->foreign(['tenant_id', 'bom_id'])
                ->references(['tenant_id', 'id'])
                ->on('boms')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['tenant_id', 'bom_id']);
            $table->dropColumn('bom_id');
        });
    }
};
