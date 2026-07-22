{{--
    DevTools guard.

    Warns a learner whose browser DevTools appear to be open, and — if they do
    not close them within a countdown — records the event and signs them out for
    an admin to review.

    HONEST LIMITS (read before relying on this):
      • There is no browser API for "are DevTools open". This uses a heuristic
        (the gap between the window's outer and inner size), which only catches
        DOCKED DevTools. DevTools opened in a SEPARATE window are not detected.
      • It can false-positive on high browser zoom or some extensions. That is
        why a trip only logs + signs out for review — never an automatic ban.
      • Anyone determined can bypass it entirely (open DevTools before the page
        loads, disable JavaScript, or fetch the page with a script). It deters
        casual snooping; it does not protect data. The real protection is that
        answers are no longer sent to the browser (see QuestionSanitizer).

    Admins are exempt — they use DevTools legitimately.
--}}
@auth
    @php
        $guardUser   = auth()->user();
        // Runs only when: the site-wide switch is on, the account is not an
        // admin, and this specific account has not been exempted.
        $guardActive = \App\Models\Setting::bool('devtools_guard_enabled', true)
            && ! $guardUser->isAdmin()
            && ! $guardUser->devtools_guard_disabled;
    @endphp
    @if($guardActive)
    <div id="devtools-guard-overlay" aria-live="assertive" role="alertdialog"
         style="display:none; position:fixed; inset:0; z-index:2147483647;
                background:rgba(15,23,42,.97); color:#fff; padding:24px;
                flex-direction:column; align-items:center; justify-content:center;
                text-align:center; font-family:system-ui,-apple-system,sans-serif;">
        <div style="max-width:520px;">
            <div style="font-size:56px; line-height:1; margin-bottom:16px;">⚠️</div>
            <h2 style="font-size:24px; font-weight:700; margin:0 0 12px;">
                Vui lòng đóng Developer Tools
            </h2>
            <p style="font-size:15px; line-height:1.6; color:#e2e8f0; margin:0 0 20px;">
                Hệ thống phát hiện công cụ nhà phát triển đang mở. Đây là hành vi
                bị cấm khi làm bài. Hãy đóng công cụ này ngay.
                <br><br>
                Nếu không đóng trong <strong id="devtools-guard-count">10</strong> giây,
                phiên của bạn sẽ bị chấm dứt và tài khoản được chuyển cho quản trị
                viên xem xét.
            </p>
            <div style="font-size:44px; font-weight:800; color:#f87171;"
                 id="devtools-guard-count-big">10</div>
        </div>
    </div>

    <script>
    (function () {
        'use strict';

        var FLAG_URL   = @js(route('security.devtools-flag'));
        var LOGIN_URL  = @js(route('login'));
        var CSRF       = document.querySelector('meta[name="csrf-token"]');
        var COUNTDOWN  = 10;    // seconds the learner has to close DevTools
        var THRESHOLD  = 160;   // px gap that suggests docked DevTools
        var POLL_MS    = 500;

        // Skip real phones/tablets only. `(pointer: coarse)` reflects the
        // PRIMARY input, so a touchscreen laptop — whose primary pointer is the
        // trackpad/mouse (fine) — is NOT skipped, while a phone or a
        // touch-only tablet is. Do not gate on screen height: most laptops are
        // under 1024px tall, and the old check silently disabled the guard on
        // them, which is why nothing happened.
        if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) {
            return;
        }

        var overlay   = document.getElementById('devtools-guard-overlay');
        var countEls  = [
            document.getElementById('devtools-guard-count'),
            document.getElementById('devtools-guard-count-big')
        ];

        var warning   = false;   // is the overlay currently up?
        var deadline  = 0;       // timestamp the countdown ends
        var flagged   = false;   // guard against double-submit

        function devtoolsOpen() {
            // Docked DevTools shrink the viewport relative to the window frame.
            var wGap = window.outerWidth  - window.innerWidth;
            var hGap = window.outerHeight - window.innerHeight;
            return wGap > THRESHOLD || hGap > THRESHOLD;
        }

        function showOverlay() {
            warning  = true;
            deadline = Date.now() + COUNTDOWN * 1000;
            overlay.style.display = 'flex';
            paint();
        }

        function hideOverlay() {
            warning = false;
            overlay.style.display = 'none';
        }

        function paint() {
            var left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
            countEls.forEach(function (el) { if (el) el.textContent = left; });
        }

        function flagAndLeave() {
            if (flagged) return;
            flagged = true;

            var body = JSON.stringify({});
            fetch(FLAG_URL, {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF ? CSRF.content : ''
                },
                body: body
            }).finally(function () {
                window.location.replace(LOGIN_URL);
            });
        }

        setInterval(function () {
            var open = devtoolsOpen();

            if (!warning) {
                if (open) showOverlay();
                return;
            }

            // Overlay is up. Closing DevTools cancels everything — no flag.
            if (!open) {
                hideOverlay();
                return;
            }

            paint();
            if (Date.now() >= deadline) {
                flagAndLeave();
            }
        }, POLL_MS);
    })();
    </script>
    @endif
@endauth
