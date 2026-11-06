<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remote_scan_sessions', function (Blueprint $table) {
            $table->unsignedInteger('submit_request_seq')->default(0);
            $table->unsignedInteger('submit_handled_seq')->default(0);
            $table->string('submit_result', 30)->nullable();
            $table->string('submit_message', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('remote_scan_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'submit_request_seq',
                'submit_handled_seq',
                'submit_result',
                'submit_message',
            ]);
        });
    }
};
