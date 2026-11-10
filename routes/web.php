<?php

use App\Http\Controllers\KandangController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\SiklusAcuanController;
use App\Http\Controllers\SiklusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'peternak'])->group(function () {
    Route::resource('kandang', KandangController::class)->parameters(['kandang' => 'kandang']);
    Route::resource('siklus', SiklusController::class)->parameters(['siklus' => 'siklus']);
    Route::patch('siklus/{siklus}/selesai', [SiklusController::class, 'selesai'])->name('siklus.selesai');

    Route::get('siklus/{siklus}/standar', [SiklusAcuanController::class, 'standar'])->name('siklus.standar');
    Route::post('siklus/{siklus}/standar', [SiklusAcuanController::class, 'unggahStandar'])->name('siklus.standar.unggah');
    Route::get('template-standar-harian.csv', [SiklusAcuanController::class, 'templateStandar'])->name('siklus.standar.template');
    Route::get('siklus/{siklus}/acuan', [SiklusAcuanController::class, 'acuan'])->name('siklus.acuan');
    Route::put('siklus/{siklus}/acuan', [SiklusAcuanController::class, 'simpanAcuan'])->name('siklus.acuan.simpan');

    Route::get('siklus/{siklus}/recording', [RecordingController::class, 'index'])->name('recording.index');
    Route::get('siklus/{siklus}/recording/create', [RecordingController::class, 'create'])->name('recording.create');
    Route::post('siklus/{siklus}/recording', [RecordingController::class, 'store'])->name('recording.store');
    Route::get('recording/{recording}/edit', [RecordingController::class, 'edit'])->name('recording.edit');
    Route::put('recording/{recording}', [RecordingController::class, 'update'])->name('recording.update');
    Route::delete('recording/{recording}', [RecordingController::class, 'destroy'])->name('recording.destroy');
    Route::patch('recording/{recording}/paraf', [RecordingController::class, 'paraf'])->name('recording.paraf');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
