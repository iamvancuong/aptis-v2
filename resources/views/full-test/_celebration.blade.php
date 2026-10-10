{{--
    Chúc mừng (đạt mục tiêu) hoặc chia buồn + động viên (chưa đạt) theo
    users.target_level — chỉ trang học viên. Lời văn: App\Support\FullTestCaption.
    Biến: $aim (FullTestService::aim), $report, $fullTest.

    Pháo hoa tự vẽ bằng canvas (không thêm thư viện). Tự bắn lần đầu mở trang
    của mỗi lượt thi; sau đó bấm "Ăn mừng lại". Tắt nếu máy bật "giảm chuyển động".
--}}
@php
    // Tên gọi = chữ cuối của họ tên (tiếng Việt: "Nguyễn Văn An" → "An").
    $name = (string) \Illuminate\Support\Str::of($fullTest->user->name ?? '')->trim()->afterLast(' ');
    $caption = \App\Support\FullTestCaption::for($aim, $report, $name, $fullTest->id);
    $dat = $aim['reached'];
    $weak = $aim['weakest'];
@endphp

<div @class([
        'rounded-2xl border px-5 sm:px-6 py-5',
        'bg-emerald-50 border-emerald-200' => $dat,
        'bg-white border-gray-200' => ! $dat,
     ])
     @if($dat) x-data="fullTestFireworks('ft-celebrated-{{ $fullTest->id }}')" x-init="autoPlay()" @endif>
    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
        <x-ui.icon-badge :icon="$dat ? 'trophy' : 'flag'" :tone="$dat ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-50 text-blue-600'" />
        <div class="flex-1 min-w-0">
            <h2 class="text-lg sm:text-xl font-bold {{ $dat ? 'text-emerald-900' : 'text-gray-900' }}">{{ $caption['title'] }}</h2>
            <p class="mt-1.5 leading-relaxed {{ $dat ? 'text-emerald-800' : 'text-gray-600' }}">{{ $caption['body'] }}</p>

            <div class="flex flex-wrap gap-2 mt-4">
                @if($dat)
                    <x-button variant="secondary" size="sm" x-on:click="play()">Ăn mừng lại</x-button>
                @else
                    @if($weak)
                        <x-button size="sm" :href="route('skills.show', $weak['skill'])">Luyện {{ $weak['label'] }} ngay</x-button>
                    @endif
                    <x-button variant="secondary" size="sm" :href="route('full-test.index')">Về trang Full Test</x-button>
                @endif
            </div>
        </div>
    </div>
</div>

@if($aim['reached'])
@once
<script>
/**
 * Pháo hoa canvas: vài quả bắn từ dưới lên rồi nổ thành tia, rơi theo trọng lực.
 * Canvas phủ toàn màn hình, không nhận chuột, tự gỡ khi hết hiệu ứng.
 */
function fullTestFireworks(storageKey) {
    return {
        autoPlay() {
            let seen = false;
            try { seen = localStorage.getItem(storageKey) === '1'; } catch (e) {}
            if (seen) return;
            try { localStorage.setItem(storageKey, '1'); } catch (e) {}
            setTimeout(() => this.play(), 400);
        },
        play() {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const canvas = document.createElement('canvas');
            canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:300';
            document.body.appendChild(canvas);
            const ctx = canvas.getContext('2d');
            const dpr = window.devicePixelRatio || 1;
            const resize = () => {
                canvas.width = innerWidth * dpr; canvas.height = innerHeight * dpr;
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            };
            resize();

            const colors = ['#f43f5e', '#f59e0b', '#10b981', '#3b82f6', '#a855f7', '#facc15', '#ffffff'];
            const rockets = [], sparks = [];
            const launch = () => rockets.push({
                x: innerWidth * (0.15 + Math.random() * 0.7), y: innerHeight,
                vx: (Math.random() - 0.5) * 2, vy: -(9 + Math.random() * 4),
                color: colors[Math.floor(Math.random() * colors.length)],
            });
            const burst = (r) => {
                const n = 60 + Math.floor(Math.random() * 30);
                for (let i = 0; i < n; i++) {
                    const a = (Math.PI * 2 * i) / n, s = 2 + Math.random() * 4;
                    sparks.push({ x: r.x, y: r.y, vx: Math.cos(a) * s, vy: Math.sin(a) * s,
                                  life: 60 + Math.random() * 30, color: Math.random() < 0.3 ? '#ffffff' : r.color });
                }
            };

            let frame = 0;
            const total = 260; // ~4 giây ở 60 khung hình/giây
            const tick = () => {
                frame++;
                if (frame < total - 90 && frame % 22 === 1) { launch(); if (Math.random() < 0.5) launch(); }

                ctx.clearRect(0, 0, innerWidth, innerHeight);
                ctx.globalCompositeOperation = 'lighter';

                for (let i = rockets.length - 1; i >= 0; i--) {
                    const r = rockets[i];
                    r.x += r.vx; r.y += r.vy; r.vy += 0.15;
                    ctx.fillStyle = r.color;
                    ctx.beginPath(); ctx.arc(r.x, r.y, 2.5, 0, Math.PI * 2); ctx.fill();
                    if (r.vy >= -1) { burst(r); rockets.splice(i, 1); }
                }
                for (let i = sparks.length - 1; i >= 0; i--) {
                    const p = sparks[i];
                    p.x += p.vx; p.y += p.vy; p.vx *= 0.985; p.vy = p.vy * 0.985 + 0.06; p.life--;
                    ctx.globalAlpha = Math.max(0, p.life / 90);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(p.x, p.y, 2.5, 2.5);
                    if (p.life <= 0) sparks.splice(i, 1);
                }
                ctx.globalAlpha = 1;

                if (frame < total || sparks.length || rockets.length) {
                    requestAnimationFrame(tick);
                } else {
                    canvas.remove();
                }
            };
            requestAnimationFrame(tick);
        },
    };
}
</script>
@endonce
@endif
