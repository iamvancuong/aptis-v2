{{--
    Tiles the "milaedu.com" brand across the page, with the signed-in learner's
    email in smaller, fainter text underneath.

    This does not stop a screenshot — nothing in a browser can, and a phone
    pointed at the monitor defeats any such attempt anyway. It brands leaked
    screenshots AND makes them traceable to the account they came from, which is
    what actually deters sharing. It is a deterrent, not a lock: anyone can
    remove this element from their own copy of the page.
--}}
@auth
    @php
        $brand = 'milaedu.com';
        $email = auth()->user()->email ?? ('#' . auth()->id());

        // Brand large; email smaller and fainter just below, both on one tile.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="340" height="200">'
            . '<g transform="rotate(-30 170 100)" text-anchor="middle" '
            . 'font-family="system-ui,-apple-system,sans-serif" fill="#0f172a">'
            . '<text x="170" y="98" font-size="20" font-weight="700">' . e($brand) . '</text>'
            . '<text x="170" y="120" font-size="18" fill-opacity="0.95">' . e($email) . '</text>'
            . '</g></svg>';

        $tile = 'data:image/svg+xml;base64,' . base64_encode($svg);
    @endphp

    <div class="aptis-watermark" aria-hidden="true" style="background-image:url('{{ $tile }}')"></div>

    <style>
        .aptis-watermark {
            position: fixed;
            inset: 0;
            z-index: 30;
            pointer-events: none; /* must never intercept clicks */
            opacity: .07;
            background-repeat: repeat;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        @media print {
            .aptis-watermark { opacity: .18; }
        }
    </style>
@endauth
