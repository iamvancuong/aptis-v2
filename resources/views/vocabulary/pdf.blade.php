{{--
    PDF luyện viết (dompdf).

    dompdf chỉ hiểu CSS 2.1 + một ít CSS3: KHÔNG flex/grid — bố cục bằng bảng.
    Font DejaVu Sans đi kèm dompdf có đủ dấu tiếng Việt và ký hiệu IPA (ˈ ə ɪ ʊ);
    đổi font khác phải kiểm lại cả hai.
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Luyện viết từ vựng</title>
<style>
    @page { margin: 16mm 14mm 16mm 14mm; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { color: #1f2937; font-size: 9.5pt; line-height: 1.35; }

    .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7.5pt; color: #9ca3af; }
    .footer .page:after { content: counter(page); }

    h1 { font-size: 15pt; margin: 0 0 1mm; color: #111827; }
    .meta { font-size: 8.5pt; color: #6b7280; margin-bottom: 5mm; }
    .guide { font-size: 8pt; color: #6b7280; background: #f9fafb; border: 0.5pt solid #e5e7eb;
             padding: 2mm 3mm; margin-bottom: 5mm; }

    .word { page-break-inside: avoid; border: 0.6pt solid #e5e7eb; border-radius: 2mm;
            padding: 3mm 4mm 2mm; margin-bottom: 4mm; }
    .head td { vertical-align: baseline; padding: 0; }
    .num { width: 7mm; color: #9ca3af; font-size: 8pt; }
    .term { font-size: 14pt; font-weight: bold; color: #111827; }
    .term.long { font-size: 11pt; }
    .phon { color: #6b7280; font-size: 9pt; }
    .tag { font-size: 7pt; color: #4b5563; background: #f3f4f6; padding: 0.3mm 1.5mm; border-radius: 1mm; }
    .cefr { font-size: 7pt; color: #047857; background: #d1fae5; padding: 0.3mm 1.5mm; border-radius: 1mm; font-weight: bold; }
    .meaning { font-size: 10.5pt; font-weight: bold; color: #4338ca; margin: 1.5mm 0 0.5mm 7mm; }
    .example { font-size: 8.5pt; color: #4b5563; font-style: italic; margin-left: 7mm; }

    /* Dòng chép mờ: chữ xám nhạt đặt trên dòng kẻ để tô theo. */
    table.trace { width: 100%; border-collapse: collapse; margin-top: 2.5mm; }
    table.trace td { border-bottom: 0.6pt solid #9ca3af; padding: 0 1mm 0.5mm; height: 9mm;
                     vertical-align: bottom; color: #cbd5e1; font-size: 15pt; white-space: nowrap; }
    table.trace.long td { font-size: 11pt; white-space: normal; }

    /* Dòng trống để tự viết lại. Nét giữa mờ giúp canh chiều cao chữ thường. */
    .line { height: 9mm; border-bottom: 0.6pt solid #9ca3af; position: relative; }
    .line .mid { position: absolute; left: 0; right: 0; top: 4.5mm; border-top: 0.4pt dashed #e5e7eb; }

    .break { page-break-before: always; }
    table.quiz { width: 100%; border-collapse: collapse; }
    table.quiz td { padding: 2.2mm 1.5mm; border-bottom: 0.5pt solid #e5e7eb; vertical-align: bottom; }
    table.quiz td.n { width: 7mm; color: #9ca3af; font-size: 8pt; }
    table.quiz td.q { width: 52%; }
    table.quiz td.a { border-bottom: 0.6pt solid #9ca3af; }
    table.key { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    table.key td { padding: 1mm 1.5mm; border-bottom: 0.4pt solid #f3f4f6; width: 50%; }
</style>
</head>
<body>

<div class="footer">
    Milaedu · Sổ tay từ vựng của {{ $user->name }} · Trang <span class="page"></span>
</div>

<h1>Luyện viết từ vựng{{ $scopeLabel ? ' — ' . $scopeLabel : '' }}</h1>
<div class="meta">
    {{ $user->name }} · {{ $items->count() }} từ · in ngày {{ now()->format('d/m/Y') }}
    @if($truncated)
        · <strong>chỉ in {{ $items->count() }} từ đầu</strong> (lọc theo thư mục để in phần còn lại)
    @endif
</div>
<div class="guide">
    <strong>Cách luyện:</strong> đọc nghĩa và phiên âm → tô theo các chữ mờ → che phần trên lại,
    tự viết từ đó vào {{ $lines }} dòng trống bên dưới.
    @if($selfTest) Cuối tệp có trang <strong>tự kiểm tra</strong>: nhìn nghĩa, viết lại từ tiếng Anh. @endif
</div>

@foreach($items as $i => $item)
    @php
        $len = mb_strlen($item->term);
        $long = $len > 24;
        // Số bản chép mờ vừa một dòng A4 (~56 ký tự ở cỡ 15pt).
        $copies = $long ? 1 : max(1, min(5, intdiv(56, $len + 4)));
    @endphp
    <div class="word">
        <table class="head" style="width:100%; border-collapse:collapse;">
            <tr>
                <td class="num">{{ $i + 1 }}.</td>
                <td>
                    <span class="term {{ $long ? 'long' : '' }}">{{ $item->term }}</span>
                    @if($item->phonetic)&nbsp; <span class="phon">{{ $item->phonetic }}</span>@endif
                    &nbsp; <span class="tag">{{ $item->part_of_speech ?: $item->wordTypeLabel() }}</span>
                    @if($item->cefr) <span class="cefr">{{ $item->cefr }}</span>@endif
                </td>
            </tr>
        </table>

        <div class="meaning">{{ $item->meaning }}</div>
        @if($item->example)
            <div class="example">{{ $item->example }}</div>
        @endif

        <table class="trace {{ $long ? 'long' : '' }}">
            <tr>
                @for($c = 0; $c < $copies; $c++)
                    <td>{{ $item->term }}</td>
                @endfor
            </tr>
        </table>

        @for($l = 0; $l < $lines; $l++)
            <div class="line"><div class="mid"></div></div>
        @endfor
    </div>
@endforeach

@if($selfTest && $quiz->isNotEmpty())
    <div class="break"></div>
    <h1>Tự kiểm tra</h1>
    <div class="meta">Nhìn nghĩa tiếng Việt, viết lại từ tiếng Anh. Thứ tự đã được xáo trộn. Đáp án ở trang sau.</div>

    <table class="quiz">
        @foreach($quiz as $i => $item)
            <tr>
                <td class="n">{{ $i + 1 }}.</td>
                <td class="q">{{ $item->meaning }}</td>
                <td class="a">&nbsp;</td>
            </tr>
        @endforeach
    </table>

    <div class="break"></div>
    <h1>Đáp án</h1>
    <table class="key">
        @foreach($quiz->values()->chunk(2) as $pair)
            <tr>
                @foreach($pair as $i => $item)
                    <td><span style="color:#9ca3af">{{ $i + 1 }}.</span> <strong>{{ $item->term }}</strong></td>
                @endforeach
                @if($pair->count() === 1)<td></td>@endif
            </tr>
        @endforeach
    </table>
@endif

</body>
</html>
