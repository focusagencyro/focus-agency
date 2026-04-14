<?php

use App\Http\Controllers\FocusPageController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', [FocusPageController::class, 'home'])->name('home');
Route::get('/sitemap-preview', [FocusPageController::class, 'sitemap'])->name('sitemap.preview');
Route::get('/health', fn () => 'ok');
Route::get('/diag/db', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'ok' => true,
            'driver' => DB::connection()->getDriverName(),
            'database' => DB::connection()->getDatabaseName(),
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});
Route::get('/diag/content', function () {
    $path = storage_path('app/focus/pages.json');

    return response()->json([
        'exists' => file_exists($path),
        'path' => $path,
        'size' => file_exists($path) ? filesize($path) : null,
        'readable' => is_readable($path),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::fallback([FocusPageController::class, 'fallback'])->name('focus.fallback');
