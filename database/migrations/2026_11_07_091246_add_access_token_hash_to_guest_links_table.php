<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accepting a guest link now leaves a cookie that restores the guest after the
 * session ends. Links accepted before this change have no hash, so their guests
 * keep access only while their current session lasts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('guest_links', 'access_token_hash')) {
            return;
        }

        Schema::table('guest_links', function (Blueprint $table) {
            $table->char('access_token_hash', 64)->nullable()->after('used_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('guest_links', 'access_token_hash')) {
            return;
        }

        Schema::table('guest_links', function (Blueprint $table) {
            $table->dropColumn('access_token_hash');
        });
    }
};
