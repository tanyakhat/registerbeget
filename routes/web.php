<?php

use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Маршруты для гостей (регистрация, вход, восстановление пароля)
Route::middleware('guest')->group(function () {
    Route::get('register', [UserController::class, 'create'])->name('register');
    Route::post('register', [UserController::class, 'store'])->name('user.store');

    Route::get('login', [UserController::class, 'login'])->name('login');
    Route::post('login', [UserController::class, 'loginAuth'])->middleware('throttle:5,1')->name('login.auth');

    // Восстановление пароля
    Route::get('forgot-password', function () {
        return view('user.forgot-password');
    })->name('password.request');

    Route::post('forgot-password', [UserController::class, 'forgotPasswordStore'])->name('password.email')
        ->middleware('throttle:3,1')->name('password.email');

    Route::get('reset-password/{token}', function (string $token) {
        return view('user.reset-password', ['token' => $token]);
    })->name('password.reset');

    Route::post('reset-password', [UserController::class, 'resetPasswordUpdate'])->name('password.update');
});

// Маршруты, требующие аутентификации
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [UserController::class, 'dashboard'])->name('dashboard');

    // Верификация Email
    Route::get('verify-email', function () {
        return view('user.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', 'Verification link sent!');
    })->middleware(['throttle:3,1'])->name('verification.send');

    // Выход
    Route::get('logout', [UserController::class, 'logout'])->name('logout');
});
