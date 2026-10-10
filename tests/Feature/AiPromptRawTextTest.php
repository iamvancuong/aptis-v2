<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Prompt gửi AI là chữ thuần. Bản cũ dựng bằng {{ }} của Blade nên dấu nháy bị
 * mã hoá thành &#039; — AI thấy "I&#039;ve" trong bài học viên và sửa như lỗi.
 */
class AiPromptRawTextTest extends TestCase
{
    private const TEXT = "I've read the club's \"new\" plan & loved it <3";

    public function test_prompt_writing_giu_nguyen_dau_nhay(): void
    {
        $out = view('prompts.writing_user', [
            'part' => 2, 'wordLimit' => 'N/A', 'question' => "Write to your friend's club",
            'metadata' => [], 'studentText' => self::TEXT,
        ])->render();

        $this->assertStringContainsString(self::TEXT, $out);
        $this->assertStringContainsString("friend's club", $out);
        $this->assertStringNotContainsString('&#039;', $out);
        $this->assertStringNotContainsString('&quot;', $out);
        $this->assertStringNotContainsString('&amp;', $out);
    }

    public function test_prompt_speaking_va_tra_tu_giu_nguyen_dau_nhay(): void
    {
        $speaking = view('prompts.speaking_user', [
            'part' => 1, 'question' => 'Tell me about yourself', 'metadata' => [], 'transcript' => self::TEXT,
        ])->render();
        $vocab = view('prompts.vocab_user', ['term' => "club's", 'context' => self::TEXT])->render();

        foreach ([$speaking, $vocab] as $out) {
            $this->assertStringContainsString(self::TEXT, $out);
            $this->assertStringNotContainsString('&#039;', $out);
        }
    }
}
