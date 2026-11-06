
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_scan_events', function (Blueprint $table) {
            $table->bigIncrements('scan_event_id');
            $table->unsignedBigInteger('scan_session_id');
            $table->unsignedInteger('sequence');
            $table->string('barcode', 120);
            $table->dateTime('scanned_at');
            $table->dateTime('consumed_at')->nullable();

            $table->foreign('scan_session_id')
                ->references('scan_session_id')
                ->on('remote_scan_sessions')
                ->cascadeOnDelete();

            $table->unique(['scan_session_id', 'sequence']);

            // Nama index dibuat lebih pendek agar kompatibel dengan MySQL.
            $table->index(
                ['scan_session_id', 'consumed_at', 'scan_event_id'],
                'rse_session_consumed_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_scan_events');
    }
};
