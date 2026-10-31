<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guests upload too: a pending upload belongs to either a user or a guest link.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pending_uploads', 'guest_link_id')) {
            return;
        }

        Schema::table('pending_uploads', function (Blueprint $table) {
            $table->string('guest_link_id', 32)->nullable()->after('user_id');
            $table->foreign('guest_link_id')->references('id')->on('guest_links')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pending_uploads', 'guest_link_id')) {
            return;
        }

        Schema::table('pending_uploads', function (Blueprint $table) {
            $table->dropForeign(['guest_link_id']);
            $table->dropColumn('guest_link_id');
        });
    }
};
