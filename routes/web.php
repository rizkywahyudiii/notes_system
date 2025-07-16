<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FaceRecognitionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/update-face-data', [AuthController::class, 'updateFaceData'])->name('update.face.data');
    Route::post('/update-pin', [AuthController::class, 'updatePin'])->name('update.pin');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Face Recognition Routes
    Route::post('/api/detect-face', [FaceRecognitionController::class, 'detectFace'])->name('api.detect-face');
    Route::post('/api/capture-face', [FaceRecognitionController::class, 'captureFace'])->name('api.capture-face');
    Route::post('/api/clear-face-data', [FaceRecognitionController::class, 'clearFaceData'])->name('api.clear-face-data');

    // Note Routes
    Route::get('/dashboard', [NoteController::class, 'index'])->name('dashboard');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::get('/notes/{note}', [NoteController::class, 'show'])->name('notes.show');
    Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::post('/notes/{note}/unlock', [NoteController::class, 'unlock'])->name('notes.unlock');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('/notes/{note}/lock', [NoteController::class, 'lock'])->name('notes.lock');
});
