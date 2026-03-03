@extends('layouts.app')

@section('title', ucfirst($mockTest->skill) . ' - Kết quả thi thử')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div>
        <a href="{{ route('skills.show', $mockTest->skill) }}" class="text-blue-600 hover:text-blue-700 text-sm flex items-center gap-1 mb-3">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Quay lại {{ ucfirst($mockTest->skill) }}
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Kết quả thi thử {{ ucfirst($mockTest->skill) }}</h1>
        <p class="text-gray-500 mt-1 text-sm">
            Hoàn thành lúc {{ $mockTest->finished_at->format('H:i d/m/Y') }}
            — Thời gian: {{ gmdate('i:s', $mockTest->duration_seconds) }}
        </p>
    </div>

    {{-- Overall Score Card --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center">
            @php
                $score = $mockTest->score ?? 0;
                $scoreColor = $score >= 80 ? 'text-green-600' : ($score >= 50 ? 'text-amber-600' : 'text-red-600');
                $scoreBg = $score >= 80 ? 'from-green-50 to-emerald-50' : ($score >= 50 ? 'from-amber-50 to-yellow-50' : 'from-red-50 to-orange-50');
            @endphp
            <div class="inline-flex items-center justify-center w-32 h-32 rounded-full bg-gradient-to-br {{ $scoreBg }} mb-4 ring-4 ring-white shadow-md">
                <span class="text-4xl font-black {{ $scoreColor }}">{{ number_format($score, 0) }}%</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-1">
                @if($score >= 80) Xuất sắc! 🎉
                @elseif($score >= 60) Tốt! 👍
                @elseif($score >= 40) Cần cải thiện 📚
                @else Cần ôn tập thêm 💪
                @endif
            </h2>
            @if($mockTest->skill !== 'writing' && $mockTest->skill !== 'speaking')
                @php
                    $attempt = $attempts->first();
                    $allAnswers = $attempt?->attemptAnswers ?? collect();
                    $totalQ = $allAnswers->count();
                    $correctQ = $allAnswers->where('is_correct', true)->count();
                    $wrongQ = $allAnswers->where('is_correct', false)->count();
                    $skippedQ = $totalQ - $correctQ - $wrongQ;
                @endphp
                <div class="grid grid-cols-3 gap-3 mt-5 max-w-xs mx-auto">
                    <div class="bg-green-50 rounded-xl p-3 text-center border border-green-100">
                        <div class="text-2xl font-black text-green-600">{{ $correctQ }}</div>
                        <div class="text-xs font-semibold text-green-700 mt-0.5">✅ Đúng</div>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3 text-center border border-red-100">
                        <div class="text-2xl font-black text-red-500">{{ $wrongQ }}</div>
                        <div class="text-xs font-semibold text-red-600 mt-0.5">❌ Sai</div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-100">
                        <div class="text-2xl font-black text-gray-500">{{ $skippedQ }}</div>
                        <div class="text-xs font-semibold text-gray-500 mt-0.5">⬜ Bỏ</div>
                    </div>
                </div>
            @else
                <p class="text-amber-600 mt-2">⏳ Bài thi đang chờ giáo viên chấm điểm</p>
            @endif
        </div>

        {{-- Grading Request Section (writing/speaking) --}}
        @if(($mockTest->skill === 'writing' || $mockTest->skill === 'speaking') && $attempts->first())
            <div class="mt-8 pt-8 border-t border-gray-100 bg-indigo-50/30 -mx-8 -mb-8 px-8 pb-8 rounded-b-2xl">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Yêu cầu giáo viên chấm điểm
                        </h3>
                        <p class="text-sm text-gray-600">
                            @if(auth()->user()->isAdmin())
                                Tài khoản Admin có thể gửi yêu cầu chấm điểm <strong>không giới hạn</strong>.
                                Hiện tại bạn đã gửi <strong>{{ $gradingRequestsCount }}</strong> lượt.
                            @else
                                Bạn có tối đa <strong>2 lần</strong> yêu cầu giáo viên chấm điểm chi tiết cho kỹ năng này.
                                Hiện tại bạn đã dùng <strong>{{ $gradingRequestsCount }}/2</strong> lượt.
                            @endif
                        </p>
                    </div>
                    @if($attempts->first()->is_grading_requested)
                        <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg font-bold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Đã gởi yêu cầu
                        </div>
                    @elseif(auth()->user()->isAdmin() || $gradingRequestsCount < 2)
                        <form action="{{ route('attempts.request-grading', $attempts->first()->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-md flex items-center gap-2 transform active:scale-95">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Gởi yêu cầu chấm bài này
                            </button>
                        </form>
                    @else
                        <div class="bg-gray-100 text-gray-400 px-4 py-2 rounded-lg font-bold cursor-not-allowed">Đã hết lượt yêu cầu</div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Per-Part Score Breakdown --}}
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <h3 class="text-base font-bold text-gray-800">Kết quả từng Part</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($sectionsWithSets as $index => $section)
                @php
                    $sectionScore = $mockTest->section_scores[$index] ?? null;
                @endphp
                <div class="px-6 py-4 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shrink-0">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-800">Part {{ $section['part'] }}</div>
                        <div class="text-xs text-gray-400 mt-0.5 truncate">{{ $section['set']->quiz->title ?? '' }}</div>
                        @if($mockTest->skill !== 'writing' && $mockTest->skill !== 'speaking' && $sectionScore !== null)
                            <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                                @php $barColor = $sectionScore >= 80 ? 'bg-green-500' : ($sectionScore >= 50 ? 'bg-amber-400' : 'bg-red-400'); @endphp
                                <div class="{{ $barColor }} h-1.5 rounded-full transition-all" style="width: {{ $sectionScore }}%"></div>
                            </div>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        @if($mockTest->skill === 'writing' || $mockTest->skill === 'speaking')
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">⏳ Chờ chấm</span>
                        @elseif($sectionScore !== null)
                            @php $color = $sectionScore >= 80 ? 'green' : ($sectionScore >= 50 ? 'amber' : 'red'); @endphp
                            <span class="text-2xl font-black text-{{ $color }}-600">{{ number_format($sectionScore, 0) }}%</span>
                        @else
                            <span class="text-gray-400 text-sm">—</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Inline Per-Question Detail (Reading / Listening) --}}
    @if(in_array($mockTest->skill, ['reading', 'listening']) && $attempts->first() && $attempts->first()->attemptAnswers->count())
        @php
            $attempt = $attempts->first();
            $byPart = $attempt->attemptAnswers
                ->sortBy(fn($a) => [$a->question->part ?? 0, $a->question->order ?? 0])
                ->groupBy(fn($a) => $a->question->part ?? '?');
        @endphp

        <div class="space-y-4">
            @foreach($byPart->sortKeys() as $part => $partAnswers)
                @php
                    $pTotal = $partAnswers->count();
                    $pCorrect = $partAnswers->where('is_correct', true)->count();
                    $pPct = $pTotal > 0 ? round($pCorrect / $pTotal * 100) : 0;
                    $headerColor = $pPct >= 80 ? 'from-green-500 to-emerald-600' : ($pPct >= 50 ? 'from-amber-400 to-orange-500' : 'from-red-400 to-rose-500');
                @endphp

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    {{-- Part Header --}}
                    <div class="bg-gradient-to-r {{ $headerColor }} px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-full bg-white/20 text-white font-black flex items-center justify-center text-sm">{{ $part }}</span>
                            <div>
                                <div class="text-white font-bold">Part {{ $part }}</div>
                                <div class="text-white/80 text-xs">{{ $pCorrect }}/{{ $pTotal }} câu đúng</div>
                            </div>
                        </div>
                        <span class="text-2xl font-black text-white">{{ $pPct }}%</span>
                    </div>

                    {{-- Questions in this part --}}
                    <div class="divide-y divide-gray-100">
                        @foreach($partAnswers as $qi => $answer)
                            @php
                                $q = $answer->question;
                                $isCorrect = $answer->is_correct;
                                $userAns = $answer->answer;
                                $correctAns = $q->metadata['correct_answer'] ?? ($q->metadata['correct_answers'] ?? null);
                                $rowBg = $isCorrect ? 'bg-green-50/30' : ($isCorrect === false ? 'bg-red-50/30' : 'bg-gray-50/30');
                            @endphp
                            <div class="px-5 py-4 {{ $rowBg }}">
                                <div class="flex items-start gap-3">
                                    <span class="text-base shrink-0 mt-0.5">{{ $isCorrect ? '✅' : ($isCorrect === false ? '❌' : '⬜') }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Part {{ $q->part }} — Câu {{ $qi + 1 }}</span>
                                            @if($q->title)
                                                <span class="text-xs text-gray-500 truncate">— {{ $q->title }}</span>
                                            @endif
                                        </div>

                                        {{-- ── READING PARTS ── --}}
                                        @if($mockTest->skill === 'reading')
                                            @if($q->part == 1)
                                                {{-- Gap Fill --}}
                                                @php
                                                    $paragraphs = $q->metadata['paragraphs'] ?? [];
                                                    $correctAnswers = $q->metadata['correct_answers'] ?? [];
                                                    $choices = $q->metadata['choices'] ?? [];
                                                    $userAnswers = is_array($userAns) ? $userAns : [];
                                                @endphp
                                                <div class="text-sm text-gray-700 p-3 bg-white rounded-xl border border-gray-100 italic leading-relaxed space-y-2">
                                                    @foreach($paragraphs as $pIdx => $para)
                                                        @php
                                                            $segments = explode('[BLANK]', $para);
                                                            $uAnsIdx = $userAnswers[$pIdx] ?? null;
                                                            $cAnsIdx = $correctAnswers[$pIdx] ?? null;
                                                            $uAnsText = ($uAnsIdx !== null && isset($choices[$pIdx][$uAnsIdx])) ? $choices[$pIdx][$uAnsIdx] : '(Bỏ trống)';
                                                            $cAnsText = isset($choices[$pIdx][$cAnsIdx]) ? $choices[$pIdx][$cAnsIdx] : 'N/A';
                                                            $isParaCorrect = ($uAnsIdx == $cAnsIdx);
                                                        @endphp
                                                        <span>
                                                            @foreach($segments as $sIdx => $segment)
                                                                {{ $segment }}
                                                                @if($sIdx < count($segments) - 1)
                                                                    <span class="font-bold px-1 rounded {{ $isParaCorrect ? 'text-green-700 bg-green-50' : 'text-red-600 bg-red-50' }}">{{ $uAnsText }}</span>
                                                                    @if(!$isParaCorrect)
                                                                        <span class="text-[11px] text-green-600 font-medium"> → {{ $cAnsText }}</span>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        </span>
                                                    @endforeach
                                                </div>

                                            @elseif($q->part == 2)
                                                {{-- Sentence Ordering --}}
                                                @php
                                                    $sentences = $q->metadata['sentences'] ?? [];
                                                    $userOrder = is_array($userAns) ? $userAns : [];
                                                @endphp
                                                <div class="space-y-1.5">
                                                    <div class="flex items-start gap-3 p-2.5 bg-blue-50 rounded-lg border border-blue-100 text-sm">
                                                        <span class="font-bold text-blue-600 w-5 shrink-0">1</span>
                                                        <span class="text-blue-800">{{ $sentences[0] ?? '' }}</span>
                                                        <span class="ml-auto text-[10px] bg-blue-200 text-blue-700 px-1.5 py-0.5 rounded font-bold uppercase">Cố định</span>
                                                    </div>
                                                    @foreach($userOrder as $idx => $slot)
                                                        @php
                                                            $slotIdx = $idx + 1;
                                                            $expectedSentence = $sentences[$slotIdx] ?? 'N/A';
                                                            $providedSentence = is_array($slot) ? ($slot['text'] ?? 'N/A') : ($slot ?? 'N/A');
                                                            $isSlotCorrect = ($providedSentence === $expectedSentence);
                                                        @endphp
                                                        <div class="flex items-start gap-3 p-2.5 rounded-lg border text-sm {{ $isSlotCorrect ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' }}">
                                                            <span class="font-bold w-5 shrink-0 {{ $isSlotCorrect ? 'text-green-600' : 'text-red-500' }}">{{ $slotIdx + 1 }}</span>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="{{ $isSlotCorrect ? 'text-green-800' : 'text-red-800' }}">{{ $providedSentence }}</p>
                                                                @if(!$isSlotCorrect)
                                                                    <p class="text-[11px] text-green-600 mt-1"><span class="font-bold">Đúng:</span> {{ $expectedSentence }}</p>
                                                                @endif
                                                            </div>
                                                            @if($isSlotCorrect)
                                                                <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                            @else
                                                                <svg class="w-4 h-4 text-red-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                    @if(empty($userOrder))
                                                        <p class="text-sm text-gray-400 italic px-2">Chưa sắp xếp câu nào.</p>
                                                    @endif
                                                </div>

                                            @elseif($q->part == 3)
                                                {{-- Matching --}}
                                                @php
                                                    $questions = $q->metadata['questions'] ?? [];
                                                    $correctAns3 = $q->metadata['correct_answers'] ?? [];
                                                    $userAnswers = is_array($userAns) ? $userAns : [];
                                                @endphp
                                                <div class="space-y-2">
                                                    @foreach($questions as $qIdx => $qText)
                                                        @php
                                                            $ua = $userAnswers[$qIdx] ?? null;
                                                            $ca = $correctAns3[$qIdx] ?? null;
                                                            $ok = ($ua !== null && $ua !== '') && $ua == $ca;
                                                            $uLabel = (isset($ua) && $ua !== '') ? 'Person ' . chr(65 + (int)$ua) : '—';
                                                            $cLabel = isset($ca) ? 'Person ' . chr(65 + (int)$ca) : 'N/A';
                                                        @endphp
                                                        <div class="flex items-start gap-3 p-3 rounded-xl border text-sm {{ $ok ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' }}">
                                                            <span class="text-xs font-bold {{ $ok ? 'text-green-600' : 'text-red-500' }} w-4 shrink-0 mt-0.5">{{ $qIdx + 1 }}</span>
                                                            <p class="flex-1 text-gray-800">{{ $qText }}</p>
                                                            <div class="text-right shrink-0">
                                                                <span class="font-bold text-xs {{ $ok ? 'text-green-600' : 'text-red-600' }}">{{ $uLabel }}</span>
                                                                @if(!$ok)
                                                                    <div class="text-[10px] text-green-600 font-bold mt-0.5">→ {{ $cLabel }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                            @elseif($q->part == 4)
                                                {{-- Headings --}}
                                                @php
                                                    $paragraphs = $q->metadata['paragraphs'] ?? [];
                                                    $headings = $q->metadata['headings'] ?? [];
                                                    $correctAnswers = $q->metadata['correct_answers'] ?? [];
                                                    $userAnswers = is_array($userAns) ? $userAns : [];
                                                @endphp
                                                <div class="space-y-2">
                                                    @foreach($paragraphs as $pIdx => $pText)
                                                        @php
                                                            $ua = $userAnswers[$pIdx] ?? null;
                                                            $ca = $correctAnswers[$pIdx] ?? null;
                                                            $ok = ($ua !== null && $ua !== '') && $ua == $ca;
                                                            $uHeading = ($ua !== null && $ua !== '' && isset($headings[$ua])) ? $headings[$ua] : '(Bỏ trống)';
                                                            $cHeading = isset($headings[$ca]) ? $headings[$ca] : 'N/A';
                                                        @endphp
                                                        <div class="p-3 rounded-xl border {{ $ok ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' }}">
                                                            <div class="flex items-center gap-2 mb-1.5">
                                                                <span class="px-2 py-0.5 bg-white/70 text-gray-600 rounded-full text-[11px] font-bold border border-gray-200">Đoạn {{ $pIdx + 1 }}</span>
                                                                <span class="text-[11px] font-bold {{ $ok ? 'text-green-700' : 'text-red-600' }}">{{ $uHeading }}</span>
                                                                @if(!$ok)
                                                                    <span class="text-[11px] text-green-600 font-bold">→ {{ $cHeading }}</span>
                                                                @endif
                                                            </div>
                                                            <p class="text-xs text-gray-500 italic line-clamp-2 leading-relaxed">{{ $pText }}</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                        {{-- ── LISTENING PARTS ── --}}
                                        @elseif($mockTest->skill === 'listening')
                                            @if($q->part == 1)
                                                {{-- Single MCQ --}}
                                                @php
                                                    $choices = $q->metadata['choices'] ?? [];
                                                    $cIdx = $q->metadata['correct_answer'] ?? null;
                                                    $uIdx = $userAns;
                                                @endphp
                                                @if($q->stem)
                                                    <p class="text-sm font-medium text-gray-800 mb-2 p-3 bg-gray-50 rounded-lg border border-gray-100">{{ $q->stem }}</p>
                                                @endif
                                                <div class="grid grid-cols-1 gap-1.5">
                                                    @foreach($choices as $cIdxLoop => $cText)
                                                        @php
                                                            $isUser = ($uIdx !== null && $uIdx == $cIdxLoop);
                                                            $isCor = ($cIdx !== null && $cIdx == $cIdxLoop);
                                                            $cls = 'bg-white border-gray-100 text-gray-700';
                                                            if ($isUser && $isCor) $cls = 'bg-green-50 border-green-300 text-green-700 font-bold';
                                                            elseif ($isUser) $cls = 'bg-red-50 border-red-300 text-red-700 font-bold';
                                                            elseif ($isCor) $cls = 'bg-green-50 border-green-200 text-green-700';
                                                        @endphp
                                                        <div class="px-3 py-2 rounded-lg border text-sm flex items-center gap-2 {{ $cls }}">
                                                            <span class="w-5 h-5 rounded-full border border-current flex items-center justify-center text-[10px] shrink-0 font-bold">{{ chr(65 + $cIdxLoop) }}</span>
                                                            <span>{{ $cText }}</span>
                                                            @if($isUser && $isCor) <svg class="w-3.5 h-3.5 ml-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                            @elseif($isUser) <svg class="w-3.5 h-3.5 ml-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            @elseif($isCor) <span class="ml-auto text-[10px] text-green-600 font-bold">✓ Đúng</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($q->part == 2)
                                                {{-- Listening P2: Speaker Matching --}}
                                                @php
                                                    $items       = $q->metadata['items'] ?? [];
                                                    $choices     = $q->metadata['choices'] ?? [];
                                                    $correctAns2 = $q->metadata['correct_answers'] ?? [];
                                                    $audioFiles  = $q->metadata['audio_files'] ?? [];
                                                    $userAnswers = is_array($userAns) ? $userAns : [];
                                                @endphp
                                                @if($q->metadata['topic'] ?? $q->stem)
                                                    <p class="text-xs font-semibold text-indigo-700 mb-2 px-1">{{ $q->metadata['topic'] ?? $q->stem }}</p>
                                                @endif
                                                <div class="space-y-1.5">
                                                    @foreach($items as $sIdx => $speakerName)
                                                        @php
                                                            $uA   = $userAnswers[$sIdx] ?? null;
                                                            $cA   = $correctAns2[$sIdx] ?? null;
                                                            $isOk = ($uA !== null && $uA == $cA);
                                                            $uTxt = ($uA !== null && isset($choices[$uA])) ? $choices[$uA] : '(Bỏ trống)';
                                                            $cTxt = isset($choices[$cA]) ? $choices[$cA] : 'N/A';
                                                            $spAudio = $audioFiles[$sIdx] ?? null;
                                                        @endphp
                                                        <div class="p-2.5 rounded-xl border text-xs {{ $isOk ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' }}">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <span class="font-bold text-gray-700">{{ $speakerName }}</span>
                                                                @if($spAudio)
                                                                    <audio src="{{ asset('storage/' . $spAudio) }}" controls class="h-6 flex-1 min-w-0"></audio>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="text-gray-400">Chọn:</span>
                                                                <span class="font-bold {{ $isOk ? 'text-green-700' : 'text-red-600' }}">{{ $uTxt }}</span>
                                                                @if(!$isOk)
                                                                    <span class="text-gray-300">|</span>
                                                                    <span class="text-green-600 font-bold">✓ {{ $cTxt }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                            @elseif($q->part == 3)
                                                {{-- Listening P3: Man/Woman/Both --}}
                                                @php
                                                    $statements  = $q->metadata['statements'] ?? [];
                                                    $sharedOpts  = $q->metadata['shared_choices'] ?? [];
                                                    $correctAns3 = $q->metadata['correct_answers'] ?? [];
                                                    $userAnswers = is_array($userAns) ? $userAns : [];
                                                @endphp
                                                @if($q->metadata['topic'] ?? $q->stem)
                                                    <p class="text-xs font-semibold text-indigo-700 mb-2 px-1">{{ $q->metadata['topic'] ?? $q->stem }}</p>
                                                @endif
                                                <div class="space-y-1.5">
                                                    @foreach($statements as $stIdx => $stText)
                                                        @php
                                                            $uA   = $userAnswers[$stIdx] ?? null;
                                                            $cA   = $correctAns3[$stIdx] ?? null;
                                                            $isOk = ($uA !== null && $uA == $cA);
                                                            $uLbl = isset($sharedOpts[$uA]) ? $sharedOpts[$uA] : '(Bỏ trống)';
                                                            $cLbl = isset($sharedOpts[$cA]) ? $sharedOpts[$cA] : 'N/A';
                                                        @endphp
                                                        <div class="flex items-start gap-2 p-2.5 rounded-xl border text-xs {{ $isOk ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' }}">
                                                            <div class="flex-1"><span class="font-medium text-gray-800">{{ $stIdx + 1 }}. {{ $stText }}</span></div>
                                                            <div class="shrink-0 text-right">
                                                                <span class="font-bold {{ $isOk ? 'text-green-600' : 'text-red-600' }}">{{ $uLbl }}</span>
                                                                @if(!$isOk)
                                                                    <p class="text-[10px] text-green-600 font-bold">✓ {{ $cLbl }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                            @elseif($q->part == 4)
                                                {{-- Listening P4: Sub-questions each with choices --}}
                                                @php
                                                    $subQuestions = $q->metadata['questions'] ?? [];
                                                    $userAnswers  = is_array($userAns) ? $userAns : [];
                                                    $audioPath    = $q->audio_path ?? null;
                                                @endphp
                                                @if($audioPath)
                                                    <audio src="{{ asset('storage/' . $audioPath) }}" controls class="w-full h-8 mb-2"></audio>
                                                @endif
                                                @if($q->stem)
                                                    <p class="text-xs font-semibold text-indigo-700 mb-2 px-1">{{ $q->stem }}</p>
                                                @endif
                                                <div class="space-y-2">
                                                    @foreach($subQuestions as $sqIdx => $sq)
                                                        @php
                                                            $sqChoices = $sq['choices'] ?? [];
                                                            $sqCorrect = $sq['correct_answer'] ?? null;
                                                            $sqUser    = $userAnswers[$sqIdx] ?? null;
                                                            $sqIsOk    = ($sqUser !== null && $sqUser == $sqCorrect);
                                                            $sqAudio   = $sq['audio'] ?? null;
                                                        @endphp
                                                        <div class="p-3 rounded-xl border {{ $sqIsOk ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' }}">
                                                            @if($sqAudio)
                                                                <audio src="{{ asset('storage/' . $sqAudio) }}" controls class="w-full h-7 mb-1.5"></audio>
                                                            @endif
                                                            <p class="text-xs font-semibold text-gray-800 mb-2">{{ $sqIdx + 1 }}. {{ $sq['question'] ?? '' }}</p>
                                                            <div class="flex flex-wrap gap-1.5">
                                                                @foreach($sqChoices as $ck => $cv)
                                                                    @php
                                                                        $isU = ($sqUser !== null && $sqUser == $ck);
                                                                        $isC = ($sqCorrect !== null && $sqCorrect == $ck);
                                                                        $cc  = 'bg-white border-gray-200 text-gray-500';
                                                                        if ($isU && $isC)  $cc = 'bg-green-100 border-green-300 text-green-700 font-bold';
                                                                        elseif ($isU)      $cc = 'bg-red-100 border-red-300 text-red-700 font-bold';
                                                                        elseif ($isC)      $cc = 'bg-green-50 border-green-200 text-green-600';
                                                                    @endphp
                                                                    <span class="px-2.5 py-1 rounded-lg border text-xs {{ $cc }}">
                                                                        {{ chr(65 + $ck) }}. {{ $cv }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif

                                    </div>
                                    <div class="shrink-0 text-right ml-2">
                                        <span class="text-sm font-bold {{ $isCorrect ? 'text-green-600' : 'text-gray-300' }}">
                                            {{ $isCorrect ? '+'.number_format($answer->score, 1) : '0' }}đ
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-4">
        @if(($mockTest->skill === 'writing' || $mockTest->skill === 'speaking') && $attempts->first())
            @php
                $detailRoute = $mockTest->skill === 'writing' ? 'writingHistory.show' : 'speakingHistory.show';
            @endphp
            <a href="{{ route($detailRoute, $attempts->first()->id) }}"
               class="flex-1 text-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors shadow-md flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                Xem chi tiết đánh giá
            </a>
        @endif
        <a href="{{ route('mock-test.create', $mockTest->skill) }}"
           class="flex-1 text-center px-6 py-3 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
            🔄 Thi lại
        </a>
        <a href="{{ route('skills.show', $mockTest->skill) }}"
           class="flex-1 text-center px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
            ← Quay lại Skill
        </a>
    </div>

</div>
@endsection
