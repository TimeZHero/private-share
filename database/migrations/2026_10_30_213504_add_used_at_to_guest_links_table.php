<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest links become single-use. Links issued before this change have no
 * used_at, so each of them still works exactly once.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('guest_links', 'used_at')) {
            return;
        }

        Schema::table('guest_links', function (Blueprint $table) {
            $table->timestamp('used_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('guest_links', 'used_at')) {
            return;
        }

        Schema::table('guest_links', function (Blueprint $table) {
            $table->dropColumn('used_at');
        });
    }
};
