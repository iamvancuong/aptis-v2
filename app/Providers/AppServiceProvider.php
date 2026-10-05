<?php

namespace App\Providers;

use App\Models\VocabularyItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Huy hiệu "N từ cần ôn" trên menu Từ vựng. Lịch ôn chỉ có tác dụng khi
        // học viên quay lại đúng hạn — phải nhắc ở chỗ họ nhìn thấy mọi trang.
        View::composer('layouts.app', function ($view) {
            $view->with('vocabDueCount', $this->vocabDueCount());
        });
    }

    protected function vocabDueCount(): int
    {
        if (! config('aptis.vocab.enabled') || ! auth()->check()) {
            return 0;
        }

        try {
            // Một câu đếm trên index (user_id, due_at) mỗi lượt tải trang.
            return VocabularyItem::where('user_id', auth()->id())->due()->count();
        } catch (\Throwable $e) {
            // Huy hiệu chỉ là phần phụ: lỗi DB ở đây không được kéo sập mọi trang.
            Log::warning('Không đếm được từ cần ôn: ' . $e->getMessage());

            return 0;
        }
    }
}
