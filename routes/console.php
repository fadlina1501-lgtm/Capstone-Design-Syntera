<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('stock-tracking:about', function () {
    $this->info('Prototype Sistem Stock Tracking Berbasis Barcode - Line AIJ.');
})->purpose('Display project information');
