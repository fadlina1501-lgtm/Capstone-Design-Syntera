<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('role_id');
            $table->string('role_name', 60)->unique();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('user_id');
            $table->unsignedInteger('role_id');
            $table->string('username', 60)->unique();
            $table->string('password_hash');
            $table->string('full_name', 120);
            $table->string('status_account', 30)->default('active');
            $table->dateTime('last_login')->nullable();
            $table->foreign('role_id')->references('role_id')->on('roles')->restrictOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('audit_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('activity', 255);
            $table->dateTime('activity_time');
            $table->string('ip_address', 45)->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
        });

        Schema::create('manpowers', function (Blueprint $table) {
            $table->bigIncrements('manpower_id');
            $table->string('nik', 40)->unique();
            $table->string('manpower_name', 120);
            $table->string('process_stage', 80);
            $table->string('manpower_status', 30)->default('active');
        });

        Schema::create('unit_of_materials', function (Blueprint $table) {
            $table->increments('uom_id');
            $table->string('uom_code', 20)->unique();
            $table->string('uom_name', 60);
        });

        Schema::create('parts', function (Blueprint $table) {
            $table->bigIncrements('part_id');
            $table->string('part_number', 80)->unique();
            $table->string('part_name', 150);
            $table->string('part_type', 40);
            $table->string('classification', 60)->nullable();
            $table->unsignedInteger('uom_id');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_materials')->restrictOnDelete();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('supplier_id');
            $table->string('supplier_code', 40)->unique();
            $table->string('supplier_name', 120);
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->increments('location_id');
            $table->string('location_name', 80)->unique();
            $table->string('process_stage', 80);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('production_plans', function (Blueprint $table) {
            $table->bigIncrements('plan_id');
            $table->unsignedBigInteger('fg_part_id');
            $table->unsignedBigInteger('created_by');
            $table->date('plan_date');
            $table->decimal('qty_plan', 14, 3)->default(0);
            $table->string('plan_status', 30)->default('planned');
            $table->foreign('fg_part_id')->references('part_id')->on('parts')->restrictOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->restrictOnDelete();
        });

        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->bigIncrements('bom_id');
            $table->unsignedBigInteger('fg_part_id');
            $table->unsignedBigInteger('component_part_id');
            $table->decimal('qty_per_unit', 14, 4)->default(1);
            $table->foreign('fg_part_id')->references('part_id')->on('parts')->restrictOnDelete();
            $table->foreign('component_part_id')->references('part_id')->on('parts')->restrictOnDelete();
            $table->unique(['fg_part_id', 'component_part_id']);
        });

        Schema::create('box_packaging_quantities', function (Blueprint $table) {
            $table->bigIncrements('box_packaging_id');
            $table->unsignedBigInteger('part_id');
            $table->decimal('std_qty_per_box', 14, 3)->default(0);
            $table->foreign('part_id')->references('part_id')->on('parts')->cascadeOnDelete();
            $table->unique('part_id');
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->bigIncrements('sto_id');
            $table->string('sto_period', 30);
            $table->date('sto_date');
            $table->string('sto_status', 30)->default('completed');
            $table->unsignedBigInteger('created_by');
            $table->dateTime('import_time')->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->restrictOnDelete();
        });

        Schema::create('mini_cycle_counts', function (Blueprint $table) {
            $table->bigIncrements('mcc_id');
            $table->unsignedBigInteger('counted_by');
            $table->unsignedBigInteger('approve_by')->nullable();
            $table->date('count_date');
            $table->string('approval_status', 30)->default('pending');
            $table->dateTime('approval_at')->nullable();
            $table->foreign('counted_by')->references('user_id')->on('users')->restrictOnDelete();
            $table->foreign('approve_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Material boxes are created before assembly_processes. The optional assembly FK is added below.
        Schema::create('material_boxes', function (Blueprint $table) {
            $table->bigIncrements('box_id');
            $table->string('box_barcode', 120)->unique();
            $table->unsignedBigInteger('part_id');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('assembly_id')->nullable();
            $table->string('po_number', 80)->nullable();
            $table->date('delivery_date')->nullable();
            $table->unsignedInteger('box_sequence')->default(1);
            $table->decimal('qty_in_box', 14, 3)->default(0);
            $table->string('receiving_area', 80)->nullable();
            $table->string('shelf_no', 40)->nullable();
            $table->string('box_status', 30)->default('stored');
            $table->foreign('part_id')->references('part_id')->on('parts')->restrictOnDelete();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->nullOnDelete();
        });

        Schema::create('preparations', function (Blueprint $table) {
            $table->bigIncrements('preparation_id');
            $table->string('tag_code', 120)->unique();
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('qty_fg', 14, 3)->default(0);
            $table->string('preparation_status', 30)->default('prepared');
            $table->dateTime('tag_printed_at')->nullable();
            $table->dateTime('preparation_time');
            $table->foreign('plan_id')->references('plan_id')->on('production_plans')->restrictOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->restrictOnDelete();
        });

        Schema::create('assembly_processes', function (Blueprint $table) {
            $table->bigIncrements('assembly_id');
            $table->unsignedBigInteger('preparation_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('qty_produced', 14, 3)->default(0);
            $table->dateTime('assembly_time');
            $table->foreign('preparation_id')->references('preparation_id')->on('preparations')->restrictOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->restrictOnDelete();
        });

        Schema::table('material_boxes', function (Blueprint $table) {
            $table->foreign('assembly_id')->references('assembly_id')->on('assembly_processes')->nullOnDelete();
        });

        Schema::create('preparation_details', function (Blueprint $table) {
            $table->bigIncrements('preparation_detail_id');
            $table->unsignedBigInteger('preparation_id');
            $table->unsignedBigInteger('box_id');
            $table->decimal('qty_prepared', 14, 3)->default(0);
            $table->foreign('preparation_id')->references('preparation_id')->on('preparations')->cascadeOnDelete();
            $table->foreign('box_id')->references('box_id')->on('material_boxes')->restrictOnDelete();
        });

        Schema::create('part_ngs', function (Blueprint $table) {
            $table->bigIncrements('part_ng_id');
            $table->unsignedBigInteger('part_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->decimal('qty_ng', 14, 3)->default(0);
            $table->string('ng_reason', 255);
            $table->dateTime('ng_time');
            $table->foreign('part_id')->references('part_id')->on('parts')->restrictOnDelete();
            $table->foreign('plan_id')->references('plan_id')->on('production_plans')->nullOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->restrictOnDelete();
        });

        Schema::create('material_movements', function (Blueprint $table) {
            $table->bigIncrements('movement_id');
            $table->unsignedBigInteger('box_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('from_location_id')->nullable();
            $table->unsignedInteger('to_location_id');
            $table->string('movement_type', 50);
            $table->decimal('quantity', 14, 3)->default(0);
            $table->dateTime('scan_time');
            $table->foreign('box_id')->references('box_id')->on('material_boxes')->restrictOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->restrictOnDelete();
            $table->foreign('from_location_id')->references('location_id')->on('locations')->nullOnDelete();
            $table->foreign('to_location_id')->references('location_id')->on('locations')->restrictOnDelete();
            $table->index(['box_id', 'movement_id']);
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->bigIncrements('stock_id');
            $table->unsignedBigInteger('part_id');
            $table->unsignedInteger('location_id');
            $table->decimal('stock_qty', 14, 3)->default(0);
            $table->dateTime('last_updated');
            $table->foreign('part_id')->references('part_id')->on('parts')->restrictOnDelete();
            $table->foreign('location_id')->references('location_id')->on('locations')->restrictOnDelete();
            $table->unique(['part_id', 'location_id']);
        });

        Schema::create('sto_details', function (Blueprint $table) {
            $table->bigIncrements('sto_detail_id');
            $table->unsignedBigInteger('sto_id');
            $table->unsignedBigInteger('part_id');
            $table->decimal('stock_system', 14, 3)->default(0);
            $table->decimal('stock_actual', 14, 3)->default(0);
            $table->decimal('gap_quantity', 14, 3)->default(0);
            $table->string('match_status', 30)->default('match');
            $table->foreign('sto_id')->references('sto_id')->on('stock_opnames')->cascadeOnDelete();
            $table->foreign('part_id')->references('part_id')->on('parts')->restrictOnDelete();
            $table->unique(['sto_id', 'part_id']);
        });

        Schema::create('mini_cycle_count_details', function (Blueprint $table) {
            $table->bigIncrements('mcc_detail_id');
            $table->unsignedBigInteger('mcc_id');
            $table->unsignedBigInteger('part_id');
            $table->decimal('stock_system', 14, 3)->default(0);
            $table->decimal('stock_actual', 14, 3)->default(0);
            $table->decimal('gap_qty', 14, 3)->default(0);
            $table->string('match_status', 30)->default('match');
            $table->string('follow_up_status', 30)->default('open');
            $table->string('follow_up_note', 500)->nullable();
            $table->foreign('mcc_id')->references('mcc_id')->on('mini_cycle_counts')->cascadeOnDelete();
            $table->foreign('part_id')->references('part_id')->on('parts')->restrictOnDelete();
            $table->unique(['mcc_id', 'part_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sto_detail_id')->nullable();
            $table->unsignedBigInteger('mcc_id')->nullable();
            $table->string('notification_title', 150);
            $table->text('notification_message');
            $table->string('notification_type', 40)->default('info');
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at');
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('sto_detail_id')->references('sto_detail_id')->on('sto_details')->nullOnDelete();
            $table->foreign('mcc_id')->references('mcc_id')->on('mini_cycle_counts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('mini_cycle_count_details');
        Schema::dropIfExists('sto_details');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('material_movements');
        Schema::table('material_boxes', function (Blueprint $table) {
            $table->dropForeign(['assembly_id']);
        });
        Schema::dropIfExists('part_ngs');
        Schema::dropIfExists('preparation_details');
        Schema::dropIfExists('assembly_processes');
        Schema::dropIfExists('preparations');
        Schema::dropIfExists('material_boxes');
        Schema::dropIfExists('mini_cycle_counts');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('box_packaging_quantities');
        Schema::dropIfExists('bill_of_materials');
        Schema::dropIfExists('production_plans');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('parts');
        Schema::dropIfExists('unit_of_materials');
        Schema::dropIfExists('manpowers');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
