<?php

use App\Http\Controllers\SalesAssistantController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('sales-assistant', [SalesAssistantController::class, 'index'])->name('sales-assistant');
    Route::post('sales-assistant/chat', [SalesAssistantController::class, 'chat'])->middleware('throttle:30,1')->name('sales-assistant.chat');
    Route::post('sales-assistant/documents', [SalesAssistantController::class, 'storeDocument'])->middleware('throttle:10,1')->name('sales-assistant.documents.store');
});

require __DIR__ . '/settings.php';
