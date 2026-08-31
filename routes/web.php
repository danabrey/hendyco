<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/pricing', function () {
    return view('pricing');
})->name('pricing');

Route::get('/testimonials', function () {
    return view('testimonials');
})->name('testimonials');

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
