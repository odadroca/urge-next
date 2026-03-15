<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Browse;
use App\Livewire\Dashboard;
use App\Livewire\Settings;
use App\Livewire\Workspace\WorkspacePage;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/browse', Browse::class)->name('browse');
    Route::get('/prompts/{prompt:slug}', WorkspacePage::class)->name('workspace');
    Route::get('/settings', Settings::class)->name('settings');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
