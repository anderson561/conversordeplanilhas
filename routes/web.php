<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ConversionController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('uploads/create', fn() => redirect()->route('uploads.index'));
    Route::resource('uploads', UploadController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::post('uploads/{upload}/convert', [ConversionController::class, 'store'])->name('conversions.store');
    Route::get('conversions/{job}/download', [ConversionController::class, 'download'])->name('conversions.download');

    Route::apiResource('templates', \App\Http\Controllers\MappingTemplateController::class);

    // CNPJ Lookup
    Route::get('/api/cnpj/{cnpj}', [\App\Http\Controllers\CnpjLookupController::class, 'lookup'])
        ->name('cnpj.lookup');
});


require __DIR__ . '/auth.php';
