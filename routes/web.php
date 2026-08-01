<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\CustomerPortalController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\PromotionPublicController;
use App\Http\Controllers\Public\RoomPublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas publicas del hotel
|--------------------------------------------------------------------------
*/

Route::middleware('public.locale')->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('public.home');
    Route::get('/habitaciones', [RoomPublicController::class, 'index'])->name('public.rooms.index');
    Route::get('/habitaciones/{roomType:slug}', [RoomPublicController::class, 'show'])->name('public.rooms.show');
    Route::get('/promociones', [PromotionPublicController::class, 'index'])->name('public.promotions.index');
    Route::get('/contacto', [HomeController::class, 'contact'])->name('public.contact');
    Route::get('/reservar', [BookingController::class, 'create'])->name('public.booking.create');
    Route::post('/reservar', [BookingController::class, 'store'])->name('public.booking.store');
    Route::get('/reservar/exito/{reservation:code}', [BookingController::class, 'success'])->name('public.booking.success');
    Route::get('/reservar/disponibilidad', [BookingController::class, 'availability'])->name('public.booking.availability');
    Route::post('/reservar/cotizar', [BookingController::class, 'quote'])->name('public.booking.quote');
    Route::get('/mi-reserva', [CustomerPortalController::class, 'search'])->name('public.customer-portal.search');
    Route::post('/mi-reserva', [CustomerPortalController::class, 'find'])->name('public.customer-portal.find');
    Route::get('/mi-reserva/{reservation:code}', [CustomerPortalController::class, 'show'])->name('public.customer-portal.show');
    Route::post('/mi-reserva/{reservation:code}/cancelar', [CustomerPortalController::class, 'cancel'])->name('public.customer-portal.cancel');
});

/*
|--------------------------------------------------------------------------
| Rutas de autenticación AdminLTE
|--------------------------------------------------------------------------
*/

Route::get('login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');

Route::middleware('guest')->group(function () {
    Route::post('login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);

    Route::get('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('reset-password/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('email/verify', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'notice'])->name('verification.notice');

    Route::get('email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [\App\Http\Controllers\Auth\ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [\App\Http\Controllers\Auth\ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])
        ->name('logout');
});

/*
|--------------------------------------------------------------------------
| Rutas separadas por área
|--------------------------------------------------------------------------
*/

require __DIR__.'/admin.php';
require __DIR__.'/gerente.php';
require __DIR__.'/recepcion.php';
require __DIR__.'/cliente.php';
