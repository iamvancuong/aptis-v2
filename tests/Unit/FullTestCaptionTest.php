<?php

namespace Tests\Unit;

use App\Support\FullTestCaption;
use PHPUnit\Framework\TestCase;

class FullTestCaptionTest extends TestCase
{
    private function aim(array $over = []): array
    {
        return array_merge([
            'target' => 'B2', 'overall' => 'B1', 'reached' => false, 'exceeded' => false, 'gap' => 18,
            'weakest' => ['skill' => 'reading', 'label' => 'Reading', 'scale' => 20, 'level' => 'A2'],
        ], $over);
    }

    public function test_chon_dung_tinh_huong(): void
    {
        $report = ['total' => 135];
        $this->assertSame('exceeded', FullTestCaption::for($this->aim(['reached' => true, 'exceeded' => true]), $report, 'An', 1)['state']);
        $this->assertSame('reached', FullTestCaption::for($this->aim(['reached' => true]), $report, 'An', 1)['state']);
        $this->assertSame('close', FullTestCaption::for($this->aim(['gap' => FullTestCaption::CLOSE_GAP]), $report, 'An', 1)['state']);
        $this->assertSame('far', FullTestCaption::for($this->aim(['gap' => FullTestCaption::CLOSE_GAP + 1]), $report, 'An', 1)['state']);
    }

    public function test_moi_cau_deu_dien_du_va_on_dinh_theo_luot_thi(): void
    {
        $cases = [
            $this->aim(['reached' => true, 'exceeded' => true]),
            $this->aim(['reached' => true]),
            $this->aim(['gap' => 5]),
            $this->aim(['gap' => 40]),
            $this->aim(['gap' => 40, 'weakest' => null]),
        ];
        foreach ($cases as $aim) {
            foreach (['An', ''] as $name) {
                for ($seed = 0; $seed < 6; $seed++) {
                    $c = FullTestCaption::for($aim, ['total' => 135], $name, $seed);
                    $this->assertStringNotContainsString('{', $c['title'] . $c['body']);
                    $this->assertStringNotContainsString(' ,', $c['title']);
                    $this->assertSame($c, FullTestCaption::for($aim, ['total' => 135], $name, $seed));
                }
            }
        }
    }
}
