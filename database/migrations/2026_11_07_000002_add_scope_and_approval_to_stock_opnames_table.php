<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            // Existing records remain available under the General area.
            $table->string('sto_area', 40)->default('General')->after('sto_period');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('created_by');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
            $table->unsignedBigInteger('recount_of_sto_id')->nullable()->after('review_note');

            $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('recount_of_sto_id')->references('sto_id')->on('stock_opnames')->nullOnDelete();
            $table->index(['sto_area', 'sto_status'], 'stock_opnames_area_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['recount_of_sto_id']);
            $table->dropIndex('stock_opnames_area_status_index');
            $table->dropColumn([
                'sto_area',
                'reviewed_by',
                'reviewed_at',
                'review_note',
                'recount_of_sto_id',
            ]);
        });
    }
};
