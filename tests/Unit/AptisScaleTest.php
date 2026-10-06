<?php

namespace Tests\Unit;

use App\Support\AptisScale;
use PHPUnit\Framework\TestCase;

class AptisScaleTest extends TestCase
{
    public function test_percent_converts_to_aptis_scale(): void
    {
        $this->assertSame(0, AptisScale::fromPercent(0));
        $this->assertSame(25, AptisScale::fromPercent(50));
        $this->assertSame(30, AptisScale::fromPercent(60));
        $this->assertSame(50, AptisScale::fromPercent(100));
        $this->assertSame(50, AptisScale::fromPercent(130)); // chặn trần
        $this->assertSame(0, AptisScale::fromPercent(null));
    }

    public function test_writing_levels_follow_aptis_thresholds(): void
    {
        $this->assertSame('A0', AptisScale::writingLevel(10));  // 5/50
        $this->assertSame('A1', AptisScale::writingLevel(12));  // 6/50
        $this->assertSame('A2', AptisScale::writingLevel(36));  // 18/50
        $this->assertSame('A2', AptisScale::writingLevel(50));  // 25/50
        $this->assertSame('B1', AptisScale::writingLevel(52));  // 26/50
        $this->assertSame('B1', AptisScale::writingLevel(60));  // 30/50
        $this->assertSame('B2', AptisScale::writingLevel(80));  // 40/50
        $this->assertSame('C', AptisScale::writingLevel(96));   // 48/50
    }

    public function test_writing_bundle_for_views(): void
    {
        $r = AptisScale::writing(60);

        $this->assertSame(30, $r['scale']);
        $this->assertSame('B1', $r['level']);
        $this->assertArrayHasKey('badge', $r['color']);
    }
}
