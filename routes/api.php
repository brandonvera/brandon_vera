<?php

use App\Http\Controllers\payController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('pagos/receive_easy', [payController::class, 'receiveEasy']); //ruta de pago easy money
Route::post('pagos/superwalletz', [payController::class, 'receiveSuper']); //ruta de pago super walletz
Route::post('webhook-payment', [payController::class, 'handleWebhook']); //ruta de ejecuccion del webhook
