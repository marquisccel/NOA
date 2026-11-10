<?php

use App\Http\Controllers\AbkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KandangController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\OvkController;
use App\Http\Controllers\PanenController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\RhppController;
use App\Http\Controllers\SamplingController;
use App\Http\Controllers\SiklusAcuanController;
use App\Http\Controllers\SiklusController;
use Illuminate\Support\Facades\Route;

// Satu pintu masuk: tamu ke halaman masuk, pengguna yang sudah masuk ke dashboard.
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

// Recording harian: peternak dan ABK miliknya (F8.2). Hak akses dijaga kebijakan, bukan middleware peran.
Route::middleware('auth')->group(function () {
    Route::get('siklus/{siklus}/recording', [RecordingController::class, 'index'])->name('recording.index');
    Route::get('siklus/{siklus}/recording/create', [RecordingController::class, 'create'])->name('recording.create');
    Route::post('siklus/{siklus}/recording', [RecordingController::class, 'store'])->name('recording.store');
    Route::get('recording/{recording}/edit', [RecordingController::class, 'edit'])->name('recording.edit');
    Route::put('recording/{recording}', [RecordingController::class, 'update'])->name('recording.update');
    Route::delete('recording/{recording}', [RecordingController::class, 'destroy'])->name('recording.destroy');
    Route::patch('recording/{recording}/paraf', [RecordingController::class, 'paraf'])->name('recording.paraf');
});

// Kontrak, biaya, panen, dan rekonsiliasi memuat angka komersial: hanya peternak (R13).
Route::middleware(['auth', 'peternak'])->group(function () {
    Route::get('kandang/{kandang}/riwayat', [KandangController::class, 'riwayat'])->name('kandang.riwayat');
    Route::resource('kandang', KandangController::class)->parameters(['kandang' => 'kandang']);
    Route::resource('siklus', SiklusController::class)->parameters(['siklus' => 'siklus']);
    Route::patch('siklus/{siklus}/selesai', [SiklusController::class, 'selesai'])->name('siklus.selesai');

    Route::get('siklus/{siklus}/standar', [SiklusAcuanController::class, 'standar'])->name('siklus.standar');
    Route::post('siklus/{siklus}/standar', [SiklusAcuanController::class, 'unggahStandar'])->name('siklus.standar.unggah');
    Route::get('template-standar-harian.csv', [SiklusAcuanController::class, 'templateStandar'])->name('siklus.standar.template');
    Route::get('siklus/{siklus}/acuan', [SiklusAcuanController::class, 'acuan'])->name('siklus.acuan');
    Route::put('siklus/{siklus}/acuan', [SiklusAcuanController::class, 'simpanAcuan'])->name('siklus.acuan.simpan');

    Route::get('siklus/{siklus}/sampling', [SamplingController::class, 'index'])->name('sampling.index');
    Route::post('siklus/{siklus}/sampling', [SamplingController::class, 'store'])->name('sampling.store');
    Route::get('sampling/{sampling}/edit', [SamplingController::class, 'edit'])->name('sampling.edit');
    Route::put('sampling/{sampling}', [SamplingController::class, 'update'])->name('sampling.update');
    Route::delete('sampling/{sampling}', [SamplingController::class, 'destroy'])->name('sampling.destroy');

    Route::get('siklus/{siklus}/panen', [PanenController::class, 'index'])->name('panen.index');
    Route::post('siklus/{siklus}/panen', [PanenController::class, 'store'])->name('panen.store');
    Route::patch('siklus/{siklus}/retur', [PanenController::class, 'simpanRetur'])->name('panen.retur');
    Route::get('panen/{panen}/edit', [PanenController::class, 'edit'])->name('panen.edit');
    Route::put('panen/{panen}', [PanenController::class, 'update'])->name('panen.update');
    Route::delete('panen/{panen}', [PanenController::class, 'destroy'])->name('panen.destroy');
    Route::get('panen/{panen}/foto', [PanenController::class, 'foto'])->name('panen.foto');

    Route::get('siklus/{siklus}/ovk', [OvkController::class, 'index'])->name('ovk.index');
    Route::post('siklus/{siklus}/ovk', [OvkController::class, 'store'])->name('ovk.store');
    Route::get('ovk/{ovk}/edit', [OvkController::class, 'edit'])->name('ovk.edit');
    Route::put('ovk/{ovk}', [OvkController::class, 'update'])->name('ovk.update');
    Route::delete('ovk/{ovk}', [OvkController::class, 'destroy'])->name('ovk.destroy');

    Route::get('siklus/{siklus}/rhpp', [RhppController::class, 'show'])->name('rhpp.show');
    Route::put('siklus/{siklus}/rhpp', [RhppController::class, 'simpan'])->name('rhpp.simpan');
    Route::delete('siklus/{siklus}/rhpp', [RhppController::class, 'hapus'])->name('rhpp.hapus');
    Route::patch('siklus/{siklus}/rhpp/ambang', [RhppController::class, 'simpanAmbang'])->name('rhpp.ambang');
    Route::get('siklus/{siklus}/rhpp/cetak', [RhppController::class, 'cetak'])->name('rhpp.cetak');

    Route::get('siklus/{siklus}/laporan', [LaporanController::class, 'siklus'])->name('laporan.siklus');
    Route::get('kandang/{kandang}/perbandingan', [LaporanController::class, 'perbandingan'])->name('laporan.perbandingan');

    Route::get('abk', [AbkController::class, 'index'])->name('abk.index');
    Route::get('abk/create', [AbkController::class, 'create'])->name('abk.create');
    Route::post('abk', [AbkController::class, 'store'])->name('abk.store');
    Route::get('abk/{abk}/edit', [AbkController::class, 'edit'])->name('abk.edit');
    Route::put('abk/{abk}', [AbkController::class, 'update'])->name('abk.update');
    Route::post('abk/{abk}/reset-sandi', [AbkController::class, 'resetSandi'])->name('abk.reset');
    Route::patch('abk/{abk}/status', [AbkController::class, 'status'])->name('abk.status');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
