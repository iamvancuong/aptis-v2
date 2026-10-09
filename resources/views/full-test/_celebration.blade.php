{{--
    Chúc mừng / động viên theo mục tiêu (users.target_level) — chỉ trang học viên.
    Biến: $aim (FullTestService::aim), $fullTest.

    Pháo hoa tự vẽ bằng canvas (không thêm thư viện). Tự bắn lần đầu mở trang
    của mỗi lượt thi; sau đó bấm "Ăn mừng lại". Tắt nếu máy bật "giảm chuyển động".
--}}
@php
    $name = \Illuminate\Support\Str::of($fullTest->user->name ?? '')->trim()->afterLast(' ');
    $weak = $aim['weakest'];
@endphp

@if($aim['reached'])
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 text-white px-6 py-6 shadow-lg"
         x-data="fullTestFireworks('ft-celebrated-{{ $fullTest->id }}')" x-init="autoPlay()">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex-1">
                @if($aim['exceeded'])
                    <h2 class="text-2xl font-extrabold">Xuất sắc{{ $name->isNotEmpty() ? ', ' . $name : '' }}! Bạn đã vượt mục tiêu {{ $aim['target'] }}</h2>
                    <p class="mt-1 text-white/90">
                        Trình độ ước tính <strong>{{ $aim['overall'] }}</strong> với {{ $report['total'] }}/200 điểm, cao hơn cả mục tiêu đặt ra.
                        Bạn hoàn toàn có thể đặt mục tiêu cao hơn để thử thách bản thân!
                    </p>
                @else
                    <h2 class="text-2xl font-extrabold">Chúc mừng{{ $name->isNotEmpty() ? ' ' . $name : '' }}! Bạn đã chạm mục tiêu {{ $aim['target'] }}</h2>
                    <p class="mt-1 text-white/90">
                        Trình độ ước tính <strong>{{ $aim['overall'] }}</strong> với {{ $report['total'] }}/200 điểm.
                        Công sức ôn luyện đã được đền đáp — giữ vững phong độ này tới ngày thi thật nhé!
                    </p>
                @endif
            </div>
            <button type="button" @click="play()"
                    class="shrink-0 inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white/20 hover:bg-white/30 font-semibold text-sm">
                Ăn mừng lại
            </button>
        </div>
    </div>
@else
    <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex-1">
                <h2 class="text-xl font-extrabold text-indigo-900">
                    Bạn đang ở {{ $aim['overall'] }} — mục tiêu {{ $aim['target'] }} không còn xa!
                </h2>
                <p class="mt-1 text-indigo-800/90">
                    Còn khoảng <strong>{{ $aim['gap'] }} điểm</strong> (tổng 4 kỹ năng) nữa là chạm {{ $aim['target'] }}.
                    @if($weak)
                        Nên ưu tiên luyện <strong>{{ $weak['label'] }}</strong> — đang {{ $weak['scale'] }}/50 ({{ $weak['level'] }}).
                    @endif
                    Mỗi lượt luyện đều đưa bạn đến gần mục tiêu hơn, cố lên nhé!
                </p>
            </div>
        </div>
    </div>
@endif

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
