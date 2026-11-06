<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_scan_sessions', function (Blueprint $table) {
            $table->bigIncrements('scan_session_id');
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash', 64)->unique();
            $table->string('mode', 20)->default('single');
            $table->string('status', 20)->default('active');
            $table->string('latest_barcode', 120)->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->unsignedInteger('consumed_sequence')->default(0);
            $table->dateTime('expires_at');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();
            $table->index(['user_id', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_scan_sessions');
    }
};
