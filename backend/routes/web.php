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

// Public Routes
use App\Models\Feedback;
use App\Models\HighScore;

Route::get('/', function () {
    $feedbacks = Feedback::where('is_active', true)->latest()->take(6)->get();
    $highScores = HighScore::where('is_active', true)->latest()->take(8)->get();
    
    return view('welcome', compact('feedbacks', 'highScores'));
})->name('home');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware(['auth', 'user.blocked', 'session.limit'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Grammar & Vocabulary
    Route::get('/grammar', [App\Http\Controllers\GrammarController::class, 'index'])->name('grammar.index');

    // Skills
    Route::get('/skills/{skill}', [SkillController::class, 'show'])->name('skills.show');

    // Sets
    Route::get('/skills/{skill}/part/{part}/sets', [SetController::class, 'index'])->name('sets.index');
    Route::get('/sets/{set}', [SetController::class, 'show'])->name('sets.show');

    // Instructions
    Route::get('/instructions', [\App\Http\Controllers\InstructionController::class, 'index'])->name('instructions.index');
    Route::get('/instructions/{slug}', [\App\Http\Controllers\InstructionController::class, 'show'])->name('instructions.show');

    // Practice
    Route::get('/practice/{set}', [App\Http\Controllers\PracticeController::class, 'show'])->name('practice.show');
    Route::post('/practice/{set}/attempt', [App\Http\Controllers\PracticeController::class, 'store'])
        ->middleware(['throttle:10,1'])
        ->name('practice.store');
    
    // AI Writing Features
    Route::get('/ai/usage-status', [App\Http\Controllers\PracticeController::class, 'getAiUsageStatus'])->name('ai.usage-status');
    Route::post('/ai/grade-writing/{answer}', [App\Http\Controllers\PracticeController::class, 'gradeWriting'])->name('ai.grade-writing');
    
    // History (attempts routes removed — not in active use)

    // Mock Test
    Route::get('/mock-test/{skill}', [App\Http\Controllers\MockTestController::class, 'create'])->name('mock-test.create');
    Route::post('/mock-test', [App\Http\Controllers\MockTestController::class, 'start'])->name('mock-test.start');
    Route::get('/mock-test/{mockTest}/exam', [App\Http\Controllers\MockTestController::class, 'show'])->name('mock-test.show');
    Route::post('/mock-test/{mockTest}/submit', [App\Http\Controllers\MockTestController::class, 'submit'])->name('mock-test.submit');
    Route::get('/mock-test/{mockTest}/result', [App\Http\Controllers\MockTestController::class, 'result'])->name('mock-test.result');

    // Unified Student History
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/{attempt}', [HistoryController::class, 'show'])->name('history.show');
    Route::post('/attempts/{attempt}/request-grading', [HistoryController::class, 'requestGrading'])->name('attempts.request-grading');
    
    // Writing History specific
    Route::get('/writing-history', [App\Http\Controllers\HistoryController::class, 'writingIndex'])->name('writingHistory.index');
    Route::get('/writing-history/{attempt}', [App\Http\Controllers\HistoryController::class, 'writingShow'])->name('writingHistory.show');

    // Speaking History specific
    Route::get('/speaking-history', [App\Http\Controllers\HistoryController::class, 'speakingIndex'])->name('speakingHistory.index');
    Route::get('/speaking-history/{attempt}', [App\Http\Controllers\HistoryController::class, 'speakingShow'])->name('speakingHistory.show');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Leaderboard
    Route::get('/leaderboard', [\App\Http\Controllers\LeaderboardController::class, 'index'])->name('leaderboard.index');
});

use App\Http\Controllers\Admin\WritingSetController;

// ... other imports ...

// Admin routes
Route::middleware(['auth', 'user.blocked', 'session.limit', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    // Route::resource('quizzes', QuizController::class); // Removed as Quizzes are seeded
    Route::resource('sets', AdminSetController::class);
    Route::resource('writing-sets', WritingSetController::class);
    Route::resource('speaking-sets', \App\Http\Controllers\Admin\SpeakingSetController::class);
    Route::get('mock-tests/export', [\App\Http\Controllers\Admin\MockTestController::class, 'export'])->name('mock-tests.export');
    Route::get('mock-tests', [\App\Http\Controllers\Admin\MockTestController::class, 'index'])->name('mock-tests.index');
    
    // Reports
    Route::get('reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('reports.export');
    Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');

    // Grammar Sets
    Route::post('grammar-sets/{grammarSet}/save-draft', [\App\Http\Controllers\Admin\GrammarSetController::class, 'saveDraft'])->name('grammar-sets.save-draft');
    Route::resource('grammar-sets', \App\Http\Controllers\Admin\GrammarSetController::class);

    // User Management
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('users/import', [UserController::class, 'import'])->name('users.import');
    Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
    Route::post('users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
    Route::post('users/{user}/reset-violations', [UserController::class, 'resetViolations'])->name('users.reset-violations');
    Route::post('users/{user}/extend-expiration', [UserController::class, 'extendExpiration'])->name('users.extend-expiration');
    Route::post('users/{user}/reset-ai', [UserController::class, 'resetAi'])->name('users.reset-ai');
    Route::post('users/{user}/reset-speaking-ai', [UserController::class, 'resetSpeakingAi'])->name('users.reset-speaking-ai');
    Route::post('users/{user}/reset-all-ai', [UserController::class, 'resetAllAi'])->name('users.reset-all-ai');
    Route::post('users/{user}/add-ai', [UserController::class, 'addAi'])->name('users.add-ai');
    Route::resource('users', UserController::class);

    // Question Management (Phase 3)
    Route::get('quizzes/{quiz}/sets', [QuestionController::class, 'getSetsByQuiz'])->name('quizzes.sets');
    Route::get('questions', function () { return redirect()->route('admin.questions.reading'); });
    Route::post('questions/import', [QuestionController::class, 'import'])->name('questions.import');
    Route::get('questions/reading', [QuestionController::class, 'readingIndex'])->name('questions.reading');
    Route::get('questions/listening', [QuestionController::class, 'listeningIndex'])->name('questions.listening');
    Route::get('questions/create', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('questions/{question}', [QuestionController::class, 'show'])->name('questions.show');
    Route::get('questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

    // Writing Reviews
    Route::post('writing-reviews/bulk-approve', [\App\Http\Controllers\Admin\WritingReviewController::class, 'bulkApprove'])->name('writing-reviews.bulk-approve');
    Route::get('writing-reviews', [\App\Http\Controllers\Admin\WritingReviewController::class, 'index'])->name('writing-reviews.index');
    Route::get('writing-reviews/{attempt}', [\App\Http\Controllers\Admin\WritingReviewController::class, 'show'])->name('writing-reviews.show');
    Route::post('writing-reviews/{attempt}/grade', [\App\Http\Controllers\Admin\WritingReviewController::class, 'grade'])->name('writing-reviews.grade');

    // Speaking Reviews
    Route::get('speaking-reviews', [\App\Http\Controllers\Admin\SpeakingReviewController::class, 'index'])->name('speaking-reviews.index');
    Route::get('speaking-reviews/{attempt}', [\App\Http\Controllers\Admin\SpeakingReviewController::class, 'show'])->name('speaking-reviews.show');
    Route::post('speaking-reviews/{attempt}/grade', [\App\Http\Controllers\Admin\SpeakingReviewController::class, 'grade'])->name('speaking-reviews.grade');

    // Feedback & High Scores
    Route::resource('feedback', \App\Http\Controllers\Admin\FeedbackController::class);
    Route::resource('high-scores', \App\Http\Controllers\Admin\HighScoreController::class);

    // Instructions Module
    Route::resource('instructions', \App\Http\Controllers\Admin\InstructionController::class);

    // Settings
    Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
});
