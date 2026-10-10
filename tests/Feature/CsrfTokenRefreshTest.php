<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Màn luyện tập mở lâu: mã CSRF trong trang có thể hết hiệu lực (419). JS gọi
 * /csrf-token để lấy mã mới rồi gửi lại — xem postJson() trong practice/show.
 */
class CsrfTokenRefreshTest extends TestCase
{
    public function test_tra_ve_ma_csrf_cua_phien_hien_tai(): void
    {
        $res = $this->getJson(route('csrf.token'))->assertOk()->assertJsonStructure(['token']);
        $this->assertSame(session()->token(), $res->json('token'));
    }
}
