<?php

use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\SetController as AdminSetController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\SetController;
use App\Http\Controllers\SkillController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware(['auth', 'user.blocked', 'session.limit'])->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    // Skills
    Route::get('/skills/{skill}', [SkillController::class, 'show'])->name('skills.show');

    // Sets
    Route::get('/skills/{skill}/part/{part}/sets', [SetController::class, 'index'])->name('sets.index');

    // Practice (placeholder for now)
    Route::get('/practice/{set}', function () {
        return 'Practice page - coming soon';
    })->name('practice.show');

    // Mock Test (placeholder for now)
    Route::get('/mock-test/{skill}', function () {
        return 'Mock Test page - coming soon';
    })->name('mock-test.start');

    // History
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/{attempt}', [HistoryController::class, 'show'])->name('history.show');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Admin routes
Route::middleware(['auth', 'user.blocked', 'session.limit', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });
    Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');
    Route::resource('quizzes', QuizController::class);
    Route::resource('sets', AdminSetController::class);

    // User Management
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('users/import', [UserController::class, 'import'])->name('users.import');
    Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
    Route::post('users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
    Route::post('users/{user}/reset-violations', [UserController::class, 'resetViolations'])->name('users.reset-violations');
    Route::post('users/{user}/extend-expiration', [UserController::class, 'extendExpiration'])->name('users.extend-expiration');
    Route::resource('users', UserController::class);

    // Question Management (Phase 3)
    Route::get('quizzes/{quiz}/sets', [QuestionController::class, 'getSetsByQuiz'])->name('quizzes.sets');
    Route::resource('questions', QuestionController::class);
});
