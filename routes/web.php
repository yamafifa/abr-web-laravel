<?php

use App\Http\Controllers\RsdtController;
use Illuminate\Support\Facades\Route;

// 一覧（検索・ページャー）
Route::get('/', [RsdtController::class, 'index'])->name('rsdts.index');

// 編集・更新・削除。{id} は主キー 5 列を "_" でつないだもの（例：/rsdt/131016_0001001_006_001_/edit）
Route::get('/rsdt/{id}/edit', [RsdtController::class, 'edit'])->name('rsdts.edit');
Route::patch('/rsdt/{id}', [RsdtController::class, 'update'])->name('rsdts.update');
Route::delete('/rsdt/{id}', [RsdtController::class, 'destroy'])->name('rsdts.destroy');
