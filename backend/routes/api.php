<?php

use App\Http\Controllers\PrestamoController;
use Illuminate\Support\Facades\Route;

Route::post('/prestamos', [PrestamoController::class, 'store']);
Route::post('/prestamos/{id}/devolucion', [PrestamoController::class, 'devolucion']);
Route::get('/prestamos/activos', [PrestamoController::class, 'activos']);
