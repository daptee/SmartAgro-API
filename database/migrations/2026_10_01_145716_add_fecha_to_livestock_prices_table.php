<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('livestock_prices', function (Blueprint $table) {
            $table->date('fecha')->nullable()->after('periodo');
        });

        // Backfill: la fecha "real" de cada fila existente es el día de su última actualización
        DB::statement('UPDATE livestock_prices SET fecha = DATE(updated_at) WHERE fecha IS NULL');

        Schema::table('livestock_prices', function (Blueprint $table) {
            $table->date('fecha')->nullable(false)->change();
            // El unique (product_id, periodo) es el que sostiene la FK de product_id: hay que crear el
            // nuevo primero (también cubre product_id) para que MySQL pueda soltar el viejo después.
            $table->unique(['product_id', 'fecha']);
            $table->dropUnique(['product_id', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('livestock_prices', function (Blueprint $table) {
            $table->unique(['product_id', 'periodo']);
            $table->dropUnique(['product_id', 'fecha']);
            $table->dropColumn('fecha');
        });
    }
};
