<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;

// Página inicial de busca
Route::get('/', [SearchController::class, 'index'])->name('search');

// Processa busca e redireciona para lista de viagens
Route::post('/search', [SearchController::class, 'search'])->name('trips.search');

// Lista viagens com query params (?from=X&to=Y&data=Z)
Route::get('/trips', [TripController::class, 'index'])->name('trips.index');

// Exibe assentos da viagem com query params (?tripId=X&from=Y&to=Z&data=W)
Route::get('/trips/{tripId}/seats', [TripController::class, 'show'])->name('trips.seats');

// Confirma seleção de assentos
Route::post('/seats', [SeatController::class, 'store'])->name('seats.store');

// Cria reserva na API (opcional)
Route::post('/bookings', [SeatController::class, 'createBooking'])->name('bookings.create');

// API endpoints
Route::prefix('api')->group(function () {
    Route::get('/stops', [SearchController::class, 'getStops'])->name('api.stops');
    Route::post('/stops/validate', [SearchController::class, 'validateStop'])->name('api.stops.validate');
    Route::get('/companies/{id}', [TripController::class, 'getCompany'])->name('api.company');
});
