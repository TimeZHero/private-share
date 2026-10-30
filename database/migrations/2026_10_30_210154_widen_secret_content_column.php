<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content is validated up to 1 MB of ciphertext, but TEXT holds 64 KB on MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->mediumText('content')->nullable()->change();
        });
    }

    /**
     * Fails on MySQL strict mode while any secret is larger than 64 KB.
     */
    public function down(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->text('content')->nullable()->change();
        });
    }
};
