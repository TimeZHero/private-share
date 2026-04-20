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
        Schema::table('secrets', function (Blueprint $table) {
            $table->string('shared_file_id', 12)->nullable()->after('markdown_enabled');
            $table->foreign('shared_file_id')->references('id')->on('shared_files')->nullOnDelete();
        });

        Schema::table('secrets', function (Blueprint $table) {
            $table->text('content')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->text('content')->nullable(false)->change();
        });

        Schema::table('secrets', function (Blueprint $table) {
            $table->dropForeign(['shared_file_id']);
        });

        Schema::table('secrets', function (Blueprint $table) {
            $table->dropColumn('shared_file_id');
        });
    }
};
