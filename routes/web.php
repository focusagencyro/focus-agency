<?php

use App\Http\Controllers\FocusPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FocusPageController::class, 'home'])->name('home');
Route::get('/sitemap-preview', [FocusPageController::class, 'sitemap'])->name('sitemap.preview');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::fallback([FocusPageController::class, 'fallback'])->name('focus.fallback');
