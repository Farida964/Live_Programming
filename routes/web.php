<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaygroundController;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/', [PlaygroundController::class, 'index']);
Route::post('/run', [PlaygroundController::class, 'run']);
Route::post('/chat', [PlaygroundController::class, 'chat']);