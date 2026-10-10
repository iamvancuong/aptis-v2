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
        View::composer('layouts.admin', function ($view) {
            $view->with('pendingReviews', $this->adminPendingReviews());
        });

        View::composer('layouts.app', function ($view) {
            $vocabDue = $this->vocabDueCount();
            $view->with('vocabDueCount', $vocabDue);
            $view->with('headerNotifications', $this->headerNotifications($vocabDue));
        });
    }

    /**
     * Mục trong chuông thông báo ở header: bài vừa có điểm + từ đến hạn ôn.
     * Trước đây mỗi loại là một thanh màu riêng trên dashboard (4 thanh chồng
     * nhau, rối) — nay gom vào chuông để thấy được ở MỌI trang.
     *
     * @return array<int, array{icon: string, text: string, url: string, tone: string}>
     */
    protected function headerNotifications(int $vocabDue): array
    {
        if (! auth()->check()) {
            return [];
        }

        try {
            $chuaXem = \App\Models\Attempt::where('user_id', auth()->id())
                ->whereIn('skill', ['writing', 'speaking'])
                ->where('is_seen', false)
                ->whereNotNull('score')
                ->selectRaw('skill, count(*) as n')
                ->groupBy('skill')
                ->pluck('n', 'skill');
        } catch (\Throwable $e) {
            Log::warning('Không đếm được bài chưa xem: ' . $e->getMessage());
            $chuaXem = collect();
        }

        $items = [];
        if (($chuaXem['writing'] ?? 0) > 0) {
            $items[] = ['icon' => 'pencil', 'tone' => 'green', 'url' => route('writingHistory.index'),
                'text' => $chuaXem['writing'] . ' bài Writing vừa có điểm'];
        }
        if (($chuaXem['speaking'] ?? 0) > 0) {
            $items[] = ['icon' => 'mic', 'tone' => 'green', 'url' => route('speakingHistory.index'),
                'text' => $chuaXem['speaking'] . ' bài Speaking vừa có điểm'];
        }
        if ($vocabDue > 0) {
            $items[] = ['icon' => 'flame', 'tone' => 'amber', 'url' => route('vocab.review'),
                'text' => $vocabDue . ' từ vựng đến hạn ôn'];
        }

        return $items;
    }

    /**
     * Số bài thi thử còn chờ giáo viên chấm — hiện trên sidebar admin. Dùng đúng
     * định nghĩa của trang chấm (Attempt::awaitingTeacher) nên số luôn khớp.
     */
    protected function adminPendingReviews(): array
    {
        try {
            return [
                'writing'  => \App\Models\Attempt::awaitingTeacher('writing')->count(),
                'speaking' => \App\Models\Attempt::awaitingTeacher('speaking')->count(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Không đếm được bài chờ chấm: ' . $e->getMessage());

            return ['writing' => 0, 'speaking' => 0];
        }
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
