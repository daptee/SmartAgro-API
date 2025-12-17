<?php

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
        Schema::table('major_crops', function (Blueprint $table) {
            // Remove old status column if exists
            if (Schema::hasColumn('major_crops', 'status')) {
                $table->dropColumn('status');
            }

            // Add status_id field (1=Publicado, 2=Borrador)
            $table->unsignedBigInteger('status_id')->default(2)->after('data');

            // Add id_user field
            $table->unsignedBigInteger('id_user')->nullable()->after('status_id');

            // Add soft deletes
            $table->softDeletes();

            // Add foreign keys
            $table->foreign('status_id')->references('id')->on('status_reports')->onDelete('restrict');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('major_crops', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['status_id']);
            $table->dropForeign(['id_user']);

            // Drop columns
            $table->dropColumn(['status_id', 'id_user']);
            $table->dropSoftDeletes();

            // Re-add old status column
            $table->enum('status', ['draft', 'published'])->default('draft')->after('data');
        });
    }
};
