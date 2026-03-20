<?php

use App\Http\Controllers\Api\V1\PluginCouponsController;
use App\Http\Controllers\Api\V1\PluginEventsController;
use App\Http\Controllers\Api\V1\PluginOrdersController;
use App\Http\Middleware\VerifyPluginHmac;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PluginPairController;

Route::prefix('v1/plugin')->group(function () {
    Route::post('/pair', [PluginPairController::class, 'pair']);

    Route::middleware([VerifyPluginHmac::class])->group(function () {
        Route::post('/events', [PluginEventsController::class, 'ingest']);
        Route::post('/orders/upsert', [PluginOrdersController::class, 'upsert']);
        Route::post('/orders/bulk', [PluginOrdersController::class, 'bulk']);
        Route::post('/coupons/bulk', [PluginCouponsController::class, 'bulk']);
    });
});