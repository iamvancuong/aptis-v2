<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Tạo phòng Zoom qua Server-to-Server OAuth.
 *
 * Mỗi buổi hướng dẫn = 1 meeting riêng (waiting room + passcode) để hạn chế học
 * chui. Host là tài khoản Zoom của giáo viên; admin nhận start_url để mở phòng,
 * học viên nhận join_url.
 *
 * ⚠️ ZOOM_FAKE=true → không gọi Zoom thật, trả link giả để test cả luồng.
 */
class ZoomService
{
    public function isConfigured(): bool
    {
        return filled(config('zoom.account_id'))
            && filled(config('zoom.client_id'))
            && filled(config('zoom.client_secret'));
    }

    /**
     * Tạo meeting cho một buổi.
     *
     * @return array{meeting_id:string, join_url:string, start_url:string, passcode:string}
     */
    public function createMeeting(Carbon $startAt, string $topic): array
    {
        if (config('zoom.fake')) {
            return $this->fakeMeeting($startAt);
        }

        $this->assertConfigured();

        $response = Http::withToken($this->accessToken())
            ->post(rtrim(config('zoom.base_url'), '/') . '/users/' . config('zoom.host_user') . '/meetings', [
                'topic'      => $topic,
                'type'       => 2, // scheduled
                'start_time' => $startAt->format('Y-m-d\TH:i:s'),
                'duration'   => config('zoom.duration'),
                'timezone'   => config('zoom.timezone'),
                'settings'   => [
                    'waiting_room'           => true,
                    'join_before_host'       => false,
                    'mute_upon_entry'        => true,
                    'approval_type'          => 2, // không cần đăng ký
                    'meeting_authentication' => false,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Zoom tạo phòng thất bại: ' . $response->body());
        }

        return [
            'meeting_id' => (string) $response->json('id'),
            'join_url'   => $response->json('join_url', ''),
            'start_url'  => $response->json('start_url', ''),
            'passcode'   => $response->json('password', ''),
        ];
    }

    /**
     * Access token S2S — cache ~1 giờ (Zoom trả expires_in 3600s).
     */
    private function accessToken(): string
    {
        return Cache::remember('zoom_s2s_token', 3300, function () {
            $response = Http::asForm()
                ->withBasicAuth(config('zoom.client_id'), config('zoom.client_secret'))
                ->post(config('zoom.oauth_url'), [
                    'grant_type' => 'account_credentials',
                    'account_id' => config('zoom.account_id'),
                ]);

            if (! $response->successful() || blank($response->json('access_token'))) {
                throw new RuntimeException('Zoom lấy token thất bại: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    private function fakeMeeting(Carbon $startAt): array
    {
        $id = 'FAKE-' . $startAt->format('Ymd');

        return [
            'meeting_id' => $id,
            'join_url'   => 'https://zoom.us/j/' . $startAt->format('Ymd') . '?pwd=milaedu-demo',
            'start_url'  => 'https://zoom.us/s/' . $startAt->format('Ymd') . '?zak=demo-host-token',
            'passcode'   => substr(md5($id), 0, 6),
        ];
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình Zoom (ZOOM_ACCOUNT_ID/CLIENT_ID/CLIENT_SECRET trong .env).');
        }
    }
}
