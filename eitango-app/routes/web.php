<?php

use App\Http\Controllers\DeckController;
use App\Http\Controllers\CardController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('/decks', [DeckController::class, 'index']);
    Route::post('/decks', [DeckController::class, 'store']);
    Route::get('/decks/{deck}/edit', [DeckController::class, 'edit']);
    Route::patch('/decks/{deck}', [DeckController::class, 'update']);
    Route::delete('/decks/{deck}', [DeckController::class, 'destroy']);
    Route::get('/decks/{deck}', [DeckController::class, 'show']);
    Route::post('/decks/{deck}/cards', [CardController::class, 'store']);
    
    Route::get('/cards/{card}/edit', [CardController::class, 'edit']);
    Route::patch('/cards/{card}', [CardController::class, 'update']);
    Route::delete('/cards/{card}', [CardController::class, 'destroy']);
    
    Route::get('/decks/{deck}/scan', [DeckController::class, 'scan']);
    
    Route::post('/decks/{deck}/cards/bulk', [CardController::class, 'bulkStore']);
    Route::get('/decks/{deck}/export', [DeckController::class, 'export']);
    


});

    
require __DIR__.'/auth.php';
