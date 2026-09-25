{{--
    PDF luyện viết (dompdf).

    dompdf chỉ hiểu CSS 2.1 + một ít CSS3: KHÔNG flex/grid — bố cục bằng bảng.

    Font: Be Vietnam Pro (giống website, giấy phép OFL — resources/fonts). Bản
    fontsource tách hai tệp latin / vietnamese, nên khai báo thành HAI family và
    để dompdf tự lấy ký tự còn thiếu ở family sau. DejaVu Sans (có sẵn trong
    dompdf) đứng cuối để hiện ký hiệu IPA (ˈ ə ɪ ʊ) mà Be Vietnam Pro không có.
    dompdf chỉ nhận TTF trong @font-face, và cần `storage/fonts` ghi được.

    Thang chữ cố định 5 cỡ — thêm cỡ mới thì dùng lại một trong 5 cỡ này:
      20pt tiêu đề trang · 13pt từ vựng · 10pt nội dung · 8.5pt phụ · 7pt nhãn
--}}
@php
    $fontDir = 'file://' . str_replace('\\', '/', resource_path('fonts/be-vietnam-pro'));
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Luyện viết từ vựng</title>
<style>
    @foreach([400, 600, 700] as $w)
        @font-face { font-family: 'BVP'; font-weight: {{ $w }}; src: url('{{ $fontDir }}/be-vietnam-pro-latin-{{ $w }}-normal.ttf') format('truetype'); }
        @font-face { font-family: 'BVP VI'; font-weight: {{ $w }}; src: url('{{ $fontDir }}/be-vietnam-pro-vietnamese-{{ $w }}-normal.ttf') format('truetype'); }
    @endforeach

    @page { margin: 18mm 16mm 18mm 16mm; }
    * { font-family: 'BVP', 'BVP VI', 'DejaVu Sans', sans-serif; }
    body { color: #0f172a; font-size: 10pt; line-height: 1.45; }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: top; padding: 0; }

    /* ── Thang chữ ── */
    .t-title { font-size: 20pt; font-weight: 700; letter-spacing: -0.2pt; line-height: 1.2; }
    .t-word  { font-size: 13pt; font-weight: 700; line-height: 1.25; }
    .t-body  { font-size: 10pt; }
    .t-small { font-size: 8.5pt; }
    .t-label { font-size: 7pt; font-weight: 600; letter-spacing: 0.8pt; text-transform: uppercase; }

    .ink    { color: #0f172a; }
    .muted  { color: #64748b; }
    .faint  { color: #94a3b8; }
    .accent { color: #4f46e5; }
    .phon   { font-family: 'DejaVu Sans', sans-serif; }

    .footer { position: fixed; bottom: -11mm; left: 0; right: 0; }
    .footer .page:after { content: counter(page); }

    /* ── Đầu trang ── */
    .cover { padding-bottom: 5mm; margin-bottom: 6mm; border-bottom: 0.8pt solid #0f172a; }
    .steps td { padding-right: 4mm; }
    .step-n { color: #4f46e5; font-weight: 700; }

    /* ── Một từ ── */
    .word { page-break-inside: avoid; padding: 4mm 0 5mm; border-bottom: 0.5pt solid #e2e8f0; }
    .idx  { width: 9mm; padding-top: 1mm; }
    .chip { display: inline-block; padding: 0.4mm 2mm; border-radius: 1mm; background: #f1f5f9; color: #475569; }
    .chip-cefr { background: #ecfdf5; color: #047857; }
    .gap-s { height: 1.2mm; }
    .gap-m { height: 3mm; }

    /* Vùng viết: dòng chép mờ + dòng trống, mỗi dòng cao 9mm như vở kẻ. */
    .rule td { height: 9mm; vertical-align: bottom; border-bottom: 0.6pt solid #cbd5e1; padding: 0 0 0.8mm; }
    .trace td { color: #d4dae3; font-weight: 400; }
    .blank td { position: relative; }
    .blank .mid { position: absolute; left: 0; right: 0; top: 4.5mm; border-top: 0.4pt dashed #e2e8f0; }

    /* ── Tự kiểm tra / đáp án ── */
    .break { page-break-before: always; }
    .quiz td { padding: 3mm 0 1.2mm; border-bottom: 0.5pt solid #e2e8f0; vertical-align: bottom; }
    .quiz td.n { width: 9mm; }
    .quiz td.q { width: 50%; padding-right: 6mm; }
    .quiz td.a { border-bottom: 0.6pt solid #cbd5e1; }
    .key td { padding: 1.6mm 0; border-bottom: 0.4pt solid #f1f5f9; width: 50%; }
</style>
</head>
<body>

<div class="footer">
    <table class="t-small faint">
        <tr>
            <td>Milaedu · Sổ tay từ vựng của {{ $user->name }}</td>
            <td style="text-align:right">Trang <span class="page"></span></td>
        </tr>
    </table>
</div>

{{-- ─────────────── Đầu trang ─────────────── --}}
<div class="cover">
    <div class="t-label accent">Milaedu · Phiếu luyện viết</div>
    <div class="gap-s"></div>
    <div class="t-title ink">{{ $scopeLabel ?: 'Từ vựng của tôi' }}</div>
    <div class="gap-s"></div>
    <div class="t-small muted">
        {{ $user->name }} &nbsp;·&nbsp; {{ $items->count() }} từ &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
        @if($truncated)
            &nbsp;·&nbsp; chỉ in {{ $items->count() }} từ đầu — lọc theo thư mục để in phần còn lại
        @endif
    </div>
    <div class="gap-m"></div>
    <table class="steps t-small muted">
        <tr>
            <td><span class="step-n">1.</span> Đọc nghĩa, phiên âm</td>
            <td><span class="step-n">2.</span> Tô theo chữ mờ</td>
            <td><span class="step-n">3.</span> Che lại, tự viết {{ $lines }} dòng</td>
            @if($selfTest)
                <td><span class="step-n">4.</span> Làm trang tự kiểm tra</td>
            @endif
        </tr>
    </table>
</div>

{{-- ─────────────── Từng từ ─────────────── --}}
@foreach($items as $i => $item)
    @php
        $len = mb_strlen($item->term);
        // Câu dài dùng cỡ nội dung thay vì cỡ từ vựng — vẫn trong thang 5 cỡ.
        $long = $len > 24;
        $sizeClass = $long ? 't-body' : 't-word';
        // Số bản chép mờ vừa một dòng (~60 ký tự ở cỡ 13pt trên khổ A4).
        $copies = $long ? 1 : max(1, min(5, intdiv(60, $len + 5)));
    @endphp
    <div class="word">
        <table>
            <tr>
                <td class="idx t-small faint" style="padding-top:1.6mm">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td>
                    <table>
                        <tr>
                            <td style="vertical-align:middle">
                                <span class="{{ $sizeClass }} ink" style="font-weight:700">{{ $item->term }}</span>
                                @if($item->phonetic)
                                    &nbsp;<span class="t-small muted phon">{{ $item->phonetic }}</span>
                                @endif
                            </td>
                            <td style="text-align:right; white-space:nowrap; width:38mm; vertical-align:middle">
                                <span class="chip t-label">{{ $item->part_of_speech ?: $item->wordTypeLabel() }}</span>
                                @if($item->cefr)
                                    <span class="chip chip-cefr t-label">{{ $item->cefr }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <div class="gap-s"></div>
                    <div class="t-body accent" style="font-weight:600">{{ $item->meaning }}</div>
                    @if($item->example)
                        <div class="t-small muted">{{ $item->example }}</div>
                    @endif

                    <table class="rule trace {{ $sizeClass }}">
                        <tr>
                            @for($c = 0; $c < $copies; $c++)
                                <td>{{ $item->term }}</td>
                            @endfor
                        </tr>
                    </table>
                    @for($l = 0; $l < $lines; $l++)
                        <table class="rule blank"><tr><td><div class="mid"></div>&nbsp;</td></tr></table>
                    @endfor
                </td>
            </tr>
        </table>
    </div>
@endforeach

{{-- ─────────────── Tự kiểm tra ─────────────── --}}
@if($selfTest && $quiz->isNotEmpty())
    <div class="break"></div>
    <div class="cover">
        <div class="t-label accent">Tự kiểm tra</div>
        <div class="gap-s"></div>
        <div class="t-title ink">Nhìn nghĩa, viết lại từ</div>
        <div class="gap-s"></div>
        <div class="t-small muted">Thứ tự đã được xáo trộn. Làm xong mới lật sang trang đáp án.</div>
    </div>

    <table class="quiz t-body">
        @foreach($quiz as $i => $item)
            <tr>
                <td class="n t-small faint">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td class="q ink">{{ $item->meaning }}</td>
                <td class="a">&nbsp;</td>
            </tr>
        @endforeach
    </table>

    <div class="break"></div>
    <div class="cover">
        <div class="t-label accent">Đáp án</div>
        <div class="gap-s"></div>
        <div class="t-title ink">Đối chiếu kết quả</div>
    </div>

    <table class="key t-body">
        @foreach($quiz->values()->chunk(2) as $pair)
            <tr>
                @foreach($pair as $i => $item)
                    <td>
                        <span class="t-small faint">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>&nbsp;
                        <span class="ink" style="font-weight:600">{{ $item->term }}</span>
                    </td>
                @endforeach
                @if($pair->count() === 1)<td></td>@endif
            </tr>
        @endforeach
    </table>
@endif

</body>
</html>
