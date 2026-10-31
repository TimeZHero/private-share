<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uploads in flight during the deploy have no owner and can no longer be
 * continued; they are purged by secrets:cleanup within 24 hours.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pending_uploads', 'user_id')) {
            return;
        }

        Schema::table('pending_uploads', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pending_uploads', 'user_id')) {
            return;
        }

        Schema::table('pending_uploads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
