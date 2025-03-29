<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;



Route::get('/', [PostController::class, 'index'])->name('home');
//Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
// Show Login & Register Forms
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');

// Handle Registration & Login
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Applying Custom Authentication Middleware
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    // Post Routes

    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');

    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
});
