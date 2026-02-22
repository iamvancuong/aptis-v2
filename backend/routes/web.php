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
    Route::get('/sets/{set}', [SetController::class, 'show'])->name('sets.show');

    // Practice
    Route::get('/practice/{set}', [App\Http\Controllers\PracticeController::class, 'show'])->name('practice.show');
    Route::post('/practice/{set}/attempt', [App\Http\Controllers\PracticeController::class, 'store'])->name('practice.store');
    
    // History
    Route::get('/attempts', [App\Http\Controllers\AttemptController::class, 'index'])->name('attempts.index');
    Route::get('/attempts/{attempt}', [App\Http\Controllers\AttemptController::class, 'show'])->name('attempts.show');

    // Mock Test
    Route::get('/mock-test/{skill}', [App\Http\Controllers\MockTestController::class, 'create'])->name('mock-test.create');
    Route::post('/mock-test', [App\Http\Controllers\MockTestController::class, 'start'])->name('mock-test.start');
    Route::get('/mock-test/{mockTest}/exam', [App\Http\Controllers\MockTestController::class, 'show'])->name('mock-test.show');
    Route::post('/mock-test/{mockTest}/submit', [App\Http\Controllers\MockTestController::class, 'submit'])->name('mock-test.submit');
    Route::get('/mock-test/{mockTest}/result', [App\Http\Controllers\MockTestController::class, 'result'])->name('mock-test.result');

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
    // Route::resource('quizzes', QuizController::class); // Removed as Quizzes are seeded
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

    // Writing Reviews
    Route::get('writing-reviews', [\App\Http\Controllers\Admin\WritingReviewController::class, 'index'])->name('writing-reviews.index');
    Route::get('writing-reviews/{attempt}', [\App\Http\Controllers\Admin\WritingReviewController::class, 'show'])->name('writing-reviews.show');
    Route::post('writing-reviews/{attemptAnswer}/grade', [\App\Http\Controllers\Admin\WritingReviewController::class, 'grade'])->name('writing-reviews.grade');
});
