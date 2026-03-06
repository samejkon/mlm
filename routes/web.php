<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TreeController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/tree', [TreeController::class, 'index'])->name('tree.index');