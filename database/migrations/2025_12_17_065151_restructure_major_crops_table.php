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
        Schema::table('major_crops', function (Blueprint $table) {
            // Remove old columns
            $table->dropColumn(['id_plan', 'icon']);

            // Rename date to make way for month/year fields
            $table->renameColumn('date', 'date_old');
        });

        Schema::table('major_crops', function (Blueprint $table) {
            // Add new columns for month and year
            $table->unsignedTinyInteger('month')->after('id'); // 1-12
            $table->unsignedSmallInteger('year')->after('month'); // e.g., 2025

            // Add status_id field (1=Publicado, 2=Borrador)
            $table->unsignedBigInteger('status_id')->default(2)->after('data');

            // Add id_user field
            $table->unsignedBigInteger('id_user')->nullable()->after('status_id');

            // Add soft deletes
            $table->softDeletes();

            // Add index for better query performance
            $table->index(['year', 'month']);

            // Add foreign keys
            $table->foreign('status_id')->references('id')->on('status_reports')->onDelete('restrict');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
        });

        // Migrate existing data from date_old to month/year
        DB::statement("
            UPDATE major_crops
            SET month = MONTH(date_old),
                year = YEAR(date_old)
        ");

        Schema::table('major_crops', function (Blueprint $table) {
            // Remove old date column
            $table->dropColumn('date_old');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('major_crops', function (Blueprint $table) {
            // Add date_old temporarily
            $table->date('date_old')->nullable();
        });

        // Migrate data back from month/year to date_old
        DB::statement("
            UPDATE major_crops
            SET date_old = CONCAT(year, '-', LPAD(month, 2, '0'), '-01')
            WHERE month IS NOT NULL AND year IS NOT NULL
        ");

        // Drop index first
        Schema::table('major_crops', function (Blueprint $table) {
            $table->dropIndex(['year', 'month']);
        });

        // Drop columns (Laravel will handle foreign keys automatically)
        Schema::table('major_crops', function (Blueprint $table) {
            $table->dropColumn(['month', 'year', 'status_id', 'id_user', 'deleted_at']);
        });

        // Rename date_old back to date
        Schema::table('major_crops', function (Blueprint $table) {
            $table->renameColumn('date_old', 'date');
        });

        // Re-add old columns
        Schema::table('major_crops', function (Blueprint $table) {
            $table->unsignedBigInteger('id_plan')->nullable();
            $table->string('icon')->nullable();
        });
    }
};
