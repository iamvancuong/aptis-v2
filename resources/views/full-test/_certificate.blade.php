{{--
    Bảng điểm Full Test — dùng chung cho trang web và PDF (dompdf).

    dompdf chỉ hiểu CSS 2.1 + một ít CSS3: KHÔNG flex/grid — bố cục bằng bảng.
    Class đều có tiền tố `ftc-` để không đụng CSS của trang web.

    CỐ Ý không dùng logo / tên British Council, Ofqual, chữ "Certificate", số
    giấy tờ tuỳ thân: đây là kết quả THI THỬ của Milaedu, không được trông như
    chứng chỉ thật (tránh bị dùng làm giấy tờ giả).

    Biến: $fullTest (đã load user), $report (FullTestService::report).
--}}
@php
    $s = $report['skills'];
    $levels = array_reverse(\App\Support\AptisScale::LEVELS); // C → A0
    $bars = [
        'Listening' => $s['listening']['level'],
        'Reading'   => $s['reading']['level'],
        'Speaking'  => $s['speaking']['level'],
        'Writing'   => $s['writing']['level'],
        'Overall'   => $report['overall'],
    ];
    $completedAt = $fullTest->finished_at ?? $fullTest->updated_at;
@endphp
<style>
    .ftc { background: #fff; color: #1e1b4b; border: 1px solid #e5e7eb; padding: 28px 30px 22px; font-size: 10pt; line-height: 1.35; }
    .ftc table { border-collapse: collapse; width: 100%; }
    .ftc td { vertical-align: top; padding: 0; }
    .ftc-brand { font-size: 15pt; font-weight: 700; color: #1e1b4b; }
    .ftc-brand-mark { width: 28px; height: 28px; text-align: center; vertical-align: middle !important; background: #4f46e5; color: #fff; border-radius: 6px; font-weight: 700; font-size: 12pt; }
    .ftc-brand-cell { vertical-align: middle !important; padding-left: 8px !important; }
    .ftc-copy { font-size: 8.5pt; font-weight: 700; text-align: right; color: #1e1b4b; }
    .ftc-kicker { color: #e11d48; font-size: 18pt; margin-top: 26px; }
    .ftc-kicker b { font-weight: 700; }
    .ftc-rule { width: 44px; height: 3px; background: #e11d48; margin: 10px 0 12px; }
    .ftc-title { font-size: 26pt; font-weight: 700; color: #1e1b6e; letter-spacing: -0.3pt; }
    .ftc-field { border-bottom: 1.5px solid #1e1b6e; font-size: 13pt; font-weight: 700; padding-bottom: 4px; color: #111827; }
    .ftc-field-sm { border-bottom: 1.5px solid #1e1b6e; font-size: 9.5pt; font-weight: 700; padding-bottom: 4px; color: #111827; }
    .ftc-label { font-size: 8.5pt; color: #374151; padding-top: 3px; }
    .ftc-box { border: 2px solid #1e1b6e; border-radius: 0 0 22px 0; padding: 14px 18px 16px; margin-top: 22px; }
    .ftc-overall { color: #e11d48; font-size: 12pt; font-weight: 700; border-bottom: 1px solid #1e1b6e; padding-bottom: 6px; margin-bottom: 10px; }
    .ftc-h { font-size: 12pt; font-weight: 700; color: #1e1b6e; padding-bottom: 8px; }
    .ftc-score td { font-size: 9pt; padding: 4px 0; border-bottom: 1px solid #d1d5db; }
    .ftc-score .ftc-th td { font-weight: 700; border-bottom: 1px solid #1e1b6e; }
    .ftc-score .ftc-num { text-align: right; }
    .ftc-chart { border-collapse: separate !important; border-spacing: 5px 0; }
    .ftc-chart td { height: 18px; font-size: 8pt; text-align: center; }
    .ftc-chart .ftc-lv { text-align: left; width: 26px; color: #374151; border-top: 1px solid #e5e7eb; }
    .ftc-chart .ftc-on { background: #e11d48; color: #fff; font-weight: 700; }
    .ftc-chart .ftc-off { border-top: 1px solid #e5e7eb; }
    .ftc-chart .ftc-x { font-size: 7pt; color: #374151; padding-top: 4px; height: auto; }
    .ftc-note { font-size: 7.5pt; color: #4b5563; margin-top: 18px; }
</style>

<div class="ftc">
    <table>
        <tr>
            <td>
                <table style="width: auto;"><tr>
                    <td class="ftc-brand-mark">M</td>
                    <td class="ftc-brand-cell"><span class="ftc-brand">Milaedu</span></td>
                </tr></table>
            </td>
            <td class="ftc-copy">Kết quả thi thử<br>Mock test result</td>
        </tr>
    </table>

    <div class="ftc-kicker"><b>Aptis</b> Full Test</div>
    <div class="ftc-rule"></div>
    <div class="ftc-title">Bảng điểm thi thử</div>

    <table style="margin-top: 22px;">
        <tr>
            <td style="width: 46%; padding-right: 22px;">
                <div class="ftc-field">{{ $fullTest->user->name }}</div>
                <div class="ftc-label">Test taker name</div>
            </td>
            <td style="width: 24%; padding-right: 22px;">
                <div class="ftc-field">{{ $fullTest->started_at?->format('j.n.Y') }}</div>
                <div class="ftc-label">Test date</div>
            </td>
            <td style="width: 30%;">
                <div class="ftc-field">{{ $fullTest->code() }}</div>
                <div class="ftc-label">Test reference</div>
            </td>
        </tr>
        <tr>
            <td style="padding-right: 22px; padding-top: 14px;">
                <table>
                    <tr>
                        <td style="width: 50%; padding-right: 12px;">
                            <div class="ftc-field-sm">Milaedu Online</div>
                            <div class="ftc-label">Test centre</div>
                        </td>
                        <td style="width: 50%;">
                            <div class="ftc-field-sm">Aptis General</div>
                            <div class="ftc-label">Format (mock)</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="padding-right: 22px; padding-top: 14px;">
                <div class="ftc-field-sm">{{ $completedAt?->format('j.n.Y') }}</div>
                <div class="ftc-label">Completed</div>
            </td>
            <td style="padding-top: 14px;">
                <div class="ftc-field-sm" style="word-break: break-all;">{{ $fullTest->user->email }}</div>
                <div class="ftc-label">Account</div>
            </td>
        </tr>
    </table>

    <div class="ftc-box">
        <div class="ftc-overall">Overall CEFR level: {{ $report['overall'] }}</div>
        <table>
            <tr>
                <td style="width: 48%; padding-right: 24px;">
                    <div class="ftc-h">Scale score</div>
                    <table class="ftc-score">
                        <tr class="ftc-th"><td>Skill name</td><td class="ftc-num">Skill score</td></tr>
                        @foreach(['listening', 'reading', 'speaking', 'writing'] as $k)
                            <tr><td>{{ $s[$k]['label'] }}</td><td class="ftc-num">{{ $s[$k]['scale'] }}/50</td></tr>
                        @endforeach
                        <tr><td>Final scale score</td><td class="ftc-num">{{ $report['total'] }}</td></tr>
                        <tr><td>{{ $s['grammar']['label'] }}</td><td class="ftc-num">{{ $s['grammar']['scale'] }}/50</td></tr>
                    </table>
                </td>
                <td style="width: 52%;">
                    <div class="ftc-h">CEFR skill profile</div>
                    <table class="ftc-chart">
                        <tr><td class="ftc-x" style="text-align: left; font-weight: 700;" colspan="6">CEFR grade</td></tr>
                        @foreach($levels as $row)
                            <tr>
                                <td class="ftc-lv">{{ $row }}</td>
                                @foreach($bars as $name => $lv)
                                    @php
                                        $on = \App\Support\AptisScale::rank($lv) >= \App\Support\AptisScale::rank($row);
                                        $top = $lv === $row;
                                    @endphp
                                    <td class="{{ $on ? 'ftc-on' : 'ftc-off' }}">{{ $top ? $lv : '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            @foreach($bars as $name => $lv)
                                <td class="ftc-x">{{ $name }}</td>
                            @endforeach
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="ftc-note">
        {{-- Mỗi câu một dòng: dompdf có thể ngắt giữa chữ có dấu khi đổi font latin/vi. --}}
        Kết quả ước tính từ bài thi thử trên Milaedu, quy đổi theo ngưỡng tham khảo của Aptis General.<br>
        Đây <strong>không phải</strong> chứng chỉ hay kết quả chính thức của British Council.
    </div>
</div>
