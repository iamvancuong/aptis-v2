<?php

namespace Tests\Feature;

use App\Models\AiLookup;
use App\Models\User;
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
            'max_devices' => 2,
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
        $boxAfterReviews = $item->fresh()->box;

        $this->actingAs($user)->postJson(route('vocab.store'), [
            'term' => 'Implement',
            'meaning' => 'nghĩa mới',
        ])->assertOk();

        $this->assertSame(1, VocabularyItem::count());
        $item->refresh();
        $this->assertSame('nghĩa mới', $item->meaning);
        $this->assertSame($boxAfterReviews, $item->box);
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

    public function test_nho_thi_gian_cach_ra_quen_thi_ve_hop_mot(): void
    {
        $user = $this->user();
        $item = VocabularyItem::create([
            'user_id' => $user->id,
            'term' => 'implement',
            'normalized_term' => 'implement',
            'meaning' => 'thực hiện',
            'due_at' => now(),
        ]);

        $item->recordReview('good');
        $this->assertSame(2, $item->box);
        $this->assertSame(3, $item->interval_days);

        $item->recordReview('hard');
        $this->assertSame(2, $item->box, 'Mơ hồ thì giữ nguyên hộp, không thăng cũng không tụt.');
        $this->assertSame(0, $item->streak);

        $item->recordReview('again');
        $this->assertSame(1, $item->box);
        $this->assertSame(1, $item->interval_days);
        $this->assertSame(3, $item->reviews_count);
    }

    public function test_phien_on_tap_chi_lay_tu_den_han_va_uu_tien_hop_thap(): void
    {
        $user = $this->user();

        $notDue = VocabularyItem::create([
            'user_id' => $user->id, 'term' => 'chuaden', 'normalized_term' => 'chuaden',
            'meaning' => 'x', 'box' => 3, 'due_at' => now()->addDays(5),
        ]);
        $dueHighBox = VocabularyItem::create([
            'user_id' => $user->id, 'term' => 'hopcao', 'normalized_term' => 'hopcao',
            'meaning' => 'x', 'box' => 4, 'due_at' => now()->subDay(),
        ]);
        $dueLowBox = VocabularyItem::create([
            'user_id' => $user->id, 'term' => 'hopthap', 'normalized_term' => 'hopthap',
            'meaning' => 'x', 'box' => 1, 'due_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('vocab.review'))->assertOk();
        $cards = $response->viewData('cards');

        $this->assertCount(2, $cards);
        $this->assertFalse($cards->contains('id', $notDue->id));
        // Từ hay quên nhất phải được gặp lại trước.
        $this->assertSame($dueLowBox->id, $cards->first()->id);
        $this->assertTrue($cards->contains('id', $dueHighBox->id));
    }

    public function test_cham_ket_qua_on_tap_qua_endpoint(): void
    {
        $user = $this->user();
        $item = VocabularyItem::create([
            'user_id' => $user->id, 'term' => 'implement', 'normalized_term' => 'implement',
            'meaning' => 'thực hiện', 'due_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('vocab.grade', $item), ['result' => 'good'])
            ->assertOk()
            ->assertJsonPath('box', 2)
            ->assertJsonPath('interval_days', 3);

        $this->actingAs($user)
            ->postJson(route('vocab.grade', $item), ['result' => 'khong-hop-le'])
            ->assertStatus(422);
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
