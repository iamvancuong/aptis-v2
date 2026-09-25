<?php

namespace Tests\Feature;

use App\Models\AiLookup;
use App\Models\User;
use App\Models\VocabFolder;
use App\Models\VocabularyItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tra từ bằng AI + sổ tay từ vựng.
 *
 * Trọng tâm là ba thứ tốn tiền hoặc gây tranh cãi nếu sai: kho đệm, hạn mức
 * theo ngày, và việc KHÔNG cho tra từ trong lúc thi thử.
 */
class VocabularyLookupTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::create([
            'name' => 'Học viên',
            'email' => $role . '-' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
            // Mỗi request trong test là một "thiết bị" mới với SessionLimit; để 2
            // thì test gửi quá 3 request sẽ bị khoá tài khoản giữa chừng.
            'max_devices' => 99,
            'violation_count' => 0,
        ]);
    }

    /** Ép OpenAI trả một kết quả cố định để test không phụ thuộc mạng. */
    private function fakeOpenAi(array $payload = []): void
    {
        config(['services.openai.key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode($payload ?: [
                            'meaning' => 'thực hiện, triển khai',
                            'part_of_speech' => 'động từ',
                            'phonetic' => '/ˈɪmplɪment/',
                            'example' => 'They implemented the new policy.',
                            'example_vi' => 'Họ đã triển khai chính sách mới.',
                            'cefr' => 'B2',
                            'note' => null,
                        ]),
                    ],
                ]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 80, 'total_tokens' => 200],
            ]),
        ]);
    }

    /* ─────────────────────────── Tra từ ─────────────────────────── */

    public function test_tra_tu_tra_ve_nghia_va_ghi_vao_kho_dem(): void
    {
        $this->fakeOpenAi();
        $user = $this->user();

        $this->actingAs($user)
            ->postJson(route('vocab.lookup'), [
                'term' => 'implement',
                'context' => 'They implemented the new policy last year.',
            ])
            ->assertOk()
            ->assertJsonPath('mode', 'word')
            ->assertJsonPath('data.meaning', 'thực hiện, triển khai')
            ->assertJsonPath('data.cefr', 'B2')
            ->assertJsonPath('saved', false);

        $this->assertDatabaseHas('ai_lookups', ['term' => 'implement', 'mode' => 'word']);
        $this->assertDatabaseHas('vocab_lookup_usages', ['user_id' => $user->id, 'count' => 1, 'api_calls' => 1]);
    }

    public function test_lan_tra_thu_hai_dung_kho_dem_khong_goi_lai_api(): void
    {
        $this->fakeOpenAi();
        $first = $this->user();
        $second = $this->user();

        $this->actingAs($first)->postJson(route('vocab.lookup'), ['term' => 'implement'])->assertOk();

        // Học viên KHÁC tra cùng từ: kho đệm dùng chung nên không được gọi API nữa.
        $this->actingAs($second)
            ->postJson(route('vocab.lookup'), ['term' => 'Implement  '])
            ->assertOk()
            ->assertJsonPath('data.meaning', 'thực hiện, triển khai');

        $this->assertSame(1, AiLookup::count());
        $this->assertSame(2, AiLookup::first()->hit_count);
        Http::assertSentCount(1);

        // Lượt trúng đệm vẫn tính vào hạn mức (chặn spam) nhưng không tính là
        // một lần gọi API (không tốn tiền).
        $this->assertDatabaseHas('vocab_lookup_usages', ['user_id' => $second->id, 'count' => 1, 'api_calls' => 0]);
    }

    public function test_cum_dai_duoc_xu_ly_nhu_mot_cau(): void
    {
        $this->fakeOpenAi(['meaning' => 'Họ đã triển khai chính sách mới vào năm ngoái.']);

        $this->actingAs($this->user())
            ->postJson(route('vocab.lookup'), ['term' => 'They implemented the new policy last year'])
            ->assertOk()
            ->assertJsonPath('mode', 'phrase');
    }

    public function test_cum_ngan_tu_hai_tu_tro_len_cung_dich_nguyen_cum(): void
    {
        // Lỗi thật: "I cycle to work" (4 từ) từng bị tra như từ đơn và AI chỉ
        // dịch "cycle" → "đạp xe".
        $this->fakeOpenAi([
            'meaning' => 'Tôi đạp xe đi làm',
            'word_type' => 'sentence',
            'part_of_speech' => 'câu',
        ]);

        $this->actingAs($this->user())
            ->postJson(route('vocab.lookup'), ['term' => 'I cycle to work'])
            ->assertOk()
            ->assertJsonPath('mode', 'phrase')
            ->assertJsonPath('data.word_type', 'sentence');

        Http::assertSent(function ($request) {
            $system = $request['messages'][0]['content'];

            return str_contains($system, 'dịch TOÀN BỘ đoạn được bôi')
                && ! str_contains($system, 'CHẾ ĐỘ: TRA MỘT TỪ');
        });

        foreach (['give up', 'in order to'] as $phrase) {
            $this->assertSame('phrase', app(\App\Services\VocabularyLookupService::class)->modeFor($phrase));
        }
        $this->assertSame('word', app(\App\Services\VocabularyLookupService::class)->modeFor('  cycle '));
    }

    public function test_ket_qua_dem_cua_prompt_cu_bi_bo_qua(): void
    {
        // Dòng đệm được ghi theo khoá kiểu cũ (không có phiên bản prompt) phải
        // bị bỏ qua, nếu không "đạp xe" cho cả câu sẽ sống mãi trong kho đệm.
        AiLookup::create([
            'hash' => hash('sha256', 'word|i cycle to work'),
            'term' => 'I cycle to work',
            'mode' => 'word',
            'payload' => ['meaning' => 'đạp xe'],
            'hit_count' => 1,
        ]);
        $this->fakeOpenAi(['meaning' => 'Tôi đạp xe đi làm', 'word_type' => 'sentence']);

        $this->actingAs($this->user())
            ->postJson(route('vocab.lookup'), ['term' => 'I cycle to work'])
            ->assertOk()
            ->assertJsonPath('data.meaning', 'Tôi đạp xe đi làm');

        Http::assertSentCount(1);
    }

    public function test_ai_quen_word_type_thi_doan_tu_loai_tu_tieng_viet(): void
    {
        $this->fakeOpenAi(['meaning' => 'từ bỏ', 'part_of_speech' => 'cụm động từ']);

        $this->actingAs($this->user())
            ->postJson(route('vocab.lookup'), ['term' => 'give up'])
            ->assertOk()
            ->assertJsonPath('data.word_type', 'phrase');

        $this->assertSame('verb', VocabularyItem::guessWordType('động từ'));
        $this->assertSame('noun', VocabularyItem::guessWordType('Danh từ'));
        $this->assertSame('other', VocabularyItem::guessWordType(null));
    }

    public function test_het_han_muc_trong_ngay_thi_tu_choi(): void
    {
        $this->fakeOpenAi();
        config(['aptis.vocab.daily_limit' => 2]);
        $user = $this->user();

        foreach (['alpha', 'beta'] as $term) {
            $this->actingAs($user)->postJson(route('vocab.lookup'), ['term' => $term])->assertOk();
        }

        $this->actingAs($user)
            ->postJson(route('vocab.lookup'), ['term' => 'gamma'])
            ->assertStatus(429)
            ->assertJsonPath('remaining', 0);
    }

    public function test_admin_khong_bi_gioi_han_luot_tra(): void
    {
        $this->fakeOpenAi();
        config(['aptis.vocab.daily_limit' => 1]);
        $admin = $this->user('admin');

        $this->actingAs($admin)->postJson(route('vocab.lookup'), ['term' => 'alpha'])->assertOk();
        $this->actingAs($admin)
            ->postJson(route('vocab.lookup'), ['term' => 'beta'])
            ->assertOk()
            ->assertJsonPath('remaining', 'unlimited');
    }

    public function test_boi_qua_dai_bi_tu_choi_truoc_khi_goi_api(): void
    {
        $this->fakeOpenAi();
        config(['aptis.vocab.max_chars' => 50]);

        $this->actingAs($this->user())
            ->postJson(route('vocab.lookup'), ['term' => str_repeat('a', 80)])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_ket_qua_khong_tra_duoc_thi_khong_ghi_vao_kho_dem(): void
    {
        // Đệm một câu trả lời hỏng nghĩa là từ đó hỏng vĩnh viễn với mọi học viên.
        $this->fakeOpenAi(['meaning' => 'Không tra được từ này.']);

        $this->actingAs($this->user())
            ->postJson(route('vocab.lookup'), ['term' => 'zzzqqq'])
            ->assertOk();

        $this->assertSame(0, AiLookup::count());
    }

    public function test_tat_cong_tac_thi_endpoint_bien_mat(): void
    {
        config(['aptis.vocab.enabled' => false]);

        $this->actingAs($this->user())
            ->postJson(route('vocab.lookup'), ['term' => 'implement'])
            ->assertNotFound();

        $this->actingAs($this->user())
            ->get(route('vocab.index'))
            ->assertNotFound();
    }

    public function test_khach_chua_dang_nhap_khong_tra_duoc(): void
    {
        $this->postJson(route('vocab.lookup'), ['term' => 'implement'])->assertUnauthorized();
    }

    /* ─────────────────────── Sổ tay từ vựng ─────────────────────── */

    public function test_luu_tu_kem_cau_goc_trong_bai(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->postJson(route('vocab.store'), [
                'term' => 'Implement',
                'meaning' => 'thực hiện, triển khai',
                'context_sentence' => 'They implemented the new policy last year.',
                'source_skill' => 'reading',
                'source_part' => 4,
            ])
            ->assertCreated();

        $item = VocabularyItem::firstOrFail();
        $this->assertSame('implement', $item->normalized_term);
        $this->assertSame('They implemented the new policy last year.', $item->context_sentence);
        // Từ mới phải ôn được ngay trong buổi học đó.
        $this->assertNotNull($item->due_at);
        $this->assertTrue($item->due_at->isPast() || $item->due_at->isCurrentMinute());
    }

    public function test_luu_lai_tu_da_co_khong_xoa_tien_do_on_tap(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson(route('vocab.store'), [
            'term' => 'implement',
            'meaning' => 'nghĩa cũ',
        ])->assertCreated();

        $item = VocabularyItem::firstOrFail();
        $item->recordReview('good');
        $item->recordReview('good');
        $stepAfterReviews = $item->fresh()->step;

        $this->actingAs($user)->postJson(route('vocab.store'), [
            'term' => 'Implement',
            'meaning' => 'nghĩa mới',
        ])->assertOk();

        $this->assertSame(1, VocabularyItem::count());
        $item->refresh();
        $this->assertSame('nghĩa mới', $item->meaning);
        $this->assertSame($stepAfterReviews, $item->step);
        $this->assertSame('learning', $item->srs_state);
    }

    public function test_khong_xem_hay_sua_duoc_so_tay_cua_nguoi_khac(): void
    {
        $owner = $this->user();
        $other = $this->user();

        $this->actingAs($owner)->postJson(route('vocab.store'), [
            'term' => 'implement',
            'meaning' => 'thực hiện',
        ])->assertCreated();

        $item = VocabularyItem::firstOrFail();

        $this->actingAs($other)->postJson(route('vocab.grade', $item), ['result' => 'good'])->assertForbidden();
        $this->actingAs($other)->delete(route('vocab.destroy', $item))->assertForbidden();

        // Kể cả admin cũng không đọc ké sổ tay người khác.
        $this->actingAs($this->user('admin'))
            ->delete(route('vocab.destroy', $item))
            ->assertForbidden();
    }

    public function test_so_tay_chi_liet_ke_tu_cua_chinh_minh(): void
    {
        $mine = $this->user();
        $theirs = $this->user();

        $this->actingAs($mine)->postJson(route('vocab.store'), ['term' => 'mineword', 'meaning' => 'của tôi'])->assertCreated();
        $this->actingAs($theirs)->postJson(route('vocab.store'), ['term' => 'theirword', 'meaning' => 'của họ'])->assertCreated();

        $this->actingAs($mine)
            ->get(route('vocab.index'))
            ->assertOk()
            ->assertSee('mineword')
            ->assertDontSee('theirword');
    }

    public function test_xuat_file_csv_co_bom_utf8(): void
    {
        $user = $this->user();
        $this->actingAs($user)->postJson(route('vocab.store'), [
            'term' => 'implement',
            'meaning' => 'thực hiện, triển khai',
        ])->assertCreated();

        $response = $this->actingAs($user)->get(route('vocab.export'));
        $response->assertOk();

        $content = $response->streamedContent();
        // Thiếu BOM là Excel trên Windows mở ra vỡ hết dấu tiếng Việt.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('thực hiện, triển khai', $content);
    }

    /* ─────────────────────────── Ôn tập ─────────────────────────── */

    private function card(User $user, array $attrs = []): VocabularyItem
    {
        static $n = 0;
        $n++;

        return VocabularyItem::create([
            'user_id' => $user->id,
            'term' => "word{$n}",
            'normalized_term' => "word{$n}",
            'meaning' => 'x',
            'due_at' => now(),
            ...$attrs,
        ]);
    }

    public function test_the_moi_di_qua_cac_buoc_1_5_10_60_phut_roi_tot_nghiep(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $item = $this->card($this->user());

        // Anki: bước đầu (1 phút) là nơi "Quên" đưa về; Nhớ lần đầu sang 5 phút.
        $this->assertSame(['again' => '1 phút', 'hard' => '1 phút', 'good' => '5 phút'], $item->previewIntervals());

        $item->recordReview('good');
        $this->assertSame('learning', $item->srs_state);
        $this->assertEqualsWithDelta(5, now()->diffInMinutes($item->due_at), 0.1);

        $item->recordReview('good');
        $this->assertEqualsWithDelta(10, now()->diffInMinutes($item->due_at), 0.1);

        $item->recordReview('good');
        $this->assertEqualsWithDelta(60, now()->diffInMinutes($item->due_at), 0.1);
        $this->assertSame('1 ngày', $item->previewIntervals()['good']);

        $item->recordReview('good');
        $this->assertSame('review', $item->srs_state);
        $this->assertSame(1, $item->interval_days);
        $this->assertTrue($item->due_at->equalTo(now()->startOfDay()->addDay()));
    }

    public function test_mo_ho_lap_lai_buoc_hien_tai_quen_ve_buoc_dau(): void
    {
        $item = $this->card($this->user());

        $item->recordReview('good');   // → bước 2 (5 phút)
        $item->recordReview('hard');
        $this->assertSame(1, $item->step, 'Mơ hồ thì lặp lại đúng bước đang học.');
        $this->assertEqualsWithDelta(5, now()->diffInMinutes($item->due_at), 0.1);

        $item->recordReview('again');
        $this->assertSame(0, $item->step);
        $this->assertEqualsWithDelta(1, now()->diffInMinutes($item->due_at), 0.1);
        $this->assertSame(3, $item->reviews_count);
    }

    public function test_on_theo_ngay_nho_thi_nhan_ease_quen_thi_reset(): void
    {
        $item = $this->card($this->user(), [
            'srs_state' => 'review', 'interval_days' => 10, 'ease' => 2500,
        ]);

        $this->assertSame(['again' => '1 phút', 'hard' => '12 ngày', 'good' => '25 ngày'], $item->previewIntervals());

        $item->recordReview('good');
        $this->assertSame(25, $item->interval_days);
        $this->assertTrue($item->isMastered(), 'Khoảng cách ≥ 21 ngày là đã thuộc.');

        $item->recordReview('again');
        $this->assertSame('relearning', $item->srs_state);
        $this->assertSame(0, $item->step);
        $this->assertSame(1, $item->lapses);
        $this->assertSame(2300, $item->ease);
        $this->assertFalse($item->isMastered());

        // Học lại xong các bước thì bắt đầu lại từ 1 ngày — quên là reset.
        foreach (range(1, 4) as $_) {
            $item->recordReview('good');
        }
        $this->assertSame('review', $item->srs_state);
        $this->assertSame(1, $item->interval_days);
    }

    public function test_phien_on_tap_uu_tien_the_dang_hoc_roi_on_roi_moi(): void
    {
        $user = $this->user();

        $notDue = $this->card($user, ['srs_state' => 'review', 'interval_days' => 5, 'due_at' => now()->addDays(5)]);
        $new = $this->card($user, ['srs_state' => 'new', 'due_at' => now()->subDays(2)]);
        $review = $this->card($user, ['srs_state' => 'review', 'interval_days' => 3, 'due_at' => now()->subDay()]);
        $learning = $this->card($user, ['srs_state' => 'learning', 'step' => 1, 'due_at' => now()->subMinute()]);

        $cards = $this->actingAs($user)->get(route('vocab.review'))->assertOk()->viewData('cards');

        $this->assertSame([$learning->id, $review->id, $new->id], $cards->pluck('id')->all());
        $this->assertFalse($cards->contains('id', $notDue->id));
        // Thẻ đang ở bước 5 phút: Mơ hồ lặp lại 5 phút.
        $this->assertSame('5 phút', $cards->first()['intervals']['hard']);
    }

    public function test_cham_ket_qua_on_tap_qua_endpoint(): void
    {
        $user = $this->user();
        $item = $this->card($user);

        $this->actingAs($user)
            ->postJson(route('vocab.grade', $item), ['result' => 'good'])
            ->assertOk()
            ->assertJsonPath('srs_state', 'learning')
            ->assertJsonPath('step', 1)
            ->assertJsonPath('intervals.good', '10 phút');

        $this->actingAs($user)
            ->postJson(route('vocab.grade', $item), ['result' => 'khong-hop-le'])
            ->assertStatus(422);
    }

    /* ─────────────────────────── Thư mục ─────────────────────────── */

    public function test_tao_thu_muc_va_luu_tu_vao_thu_muc(): void
    {
        $user = $this->user();

        $folderId = $this->actingAs($user)
            ->postJson(route('vocab.folders.store'), ['name' => 'Môi trường'])
            ->assertCreated()
            ->json('id');

        $this->actingAs($user)
            ->postJson(route('vocab.folders.store'), ['name' => 'Môi trường'])
            ->assertStatus(422);

        $this->actingAs($user)->postJson(route('vocab.store'), [
            'term' => 'pollution',
            'meaning' => 'ô nhiễm',
            'word_type' => 'noun',
            'folder_id' => $folderId,
        ])->assertCreated();

        $item = VocabularyItem::firstOrFail();
        $this->assertSame($folderId, $item->folder_id);
        $this->assertSame('noun', $item->word_type);

        $this->actingAs($user)
            ->get(route('vocab.index', ['folder' => $folderId]))
            ->assertOk()
            ->assertSee('pollution');

        $this->actingAs($user)
            ->get(route('vocab.index', ['type' => 'verb']))
            ->assertOk()
            ->assertDontSee('ô nhiễm');
    }

    public function test_khong_luu_duoc_vao_thu_muc_cua_nguoi_khac(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $folder = VocabFolder::create(['user_id' => $owner->id, 'name' => 'Của tôi']);

        $this->actingAs($other)->postJson(route('vocab.store'), [
            'term' => 'hack', 'meaning' => 'x', 'folder_id' => $folder->id,
        ])->assertStatus(422);

        $this->actingAs($other)->delete(route('vocab.folders.destroy', $folder))->assertForbidden();
    }

    public function test_xoa_thu_muc_giu_lai_tu_ben_trong(): void
    {
        $user = $this->user();
        $folder = VocabFolder::create(['user_id' => $user->id, 'name' => 'Tạm']);
        $item = $this->card($user, ['folder_id' => $folder->id]);

        $this->actingAs($user)->delete(route('vocab.folders.destroy', $folder))->assertRedirect();

        $this->assertNull($item->fresh()->folder_id);
        $this->assertSame(0, VocabFolder::count());
    }

    public function test_chuyen_thu_muc_va_on_rieng_mot_thu_muc(): void
    {
        $user = $this->user();
        $folder = VocabFolder::create(['user_id' => $user->id, 'name' => 'Chủ đề A']);
        $inFolder = $this->card($user);
        $outside = $this->card($user);

        $this->actingAs($user)
            ->patchJson(route('vocab.move', $inFolder), ['folder_id' => $folder->id])
            ->assertOk();

        $cards = $this->actingAs($user)
            ->get(route('vocab.review', ['folder' => $folder->id]))
            ->assertOk()
            ->viewData('cards');

        $this->assertSame([$inFolder->id], $cards->pluck('id')->all());
        $this->assertFalse($cards->contains('id', $outside->id));
    }

    /* ──────────────────── Ranh giới với thi thử ──────────────────── */

    public function test_trang_thi_thu_khong_gan_tra_tu(): void
    {
        /*
         * Đây là ràng buộc NGHIỆP VỤ, không phải chi tiết kỹ thuật: tra được từ
         * trong lúc thi thử thì điểm Reading mất hết ý nghĩa đánh giá. Test này
         * canh để không ai vô tình include partial vào trang thi.
         */
        foreach (['show', 'lobby'] as $view) {
            $path = resource_path("views/mock-test/{$view}.blade.php");
            $this->assertFileExists($path);

            $source = file_get_contents($path);
            $this->assertStringNotContainsString('partials.vocab-lookup', $source);
            $this->assertStringNotContainsString('data-vocab-scope', $source);
        }
    }
}
