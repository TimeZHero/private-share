<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_uploads', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('total_size');
            $table->unsignedInteger('total_chunks');
            $table->unsignedInteger('received_chunks')->default(0);
            $table->string('temp_path');
            $table->string('encryption_salt', 64)->nullable();
            $table->string('client_iv', 24)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_uploads');
    }
};
