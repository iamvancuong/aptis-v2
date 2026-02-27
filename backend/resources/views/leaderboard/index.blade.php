@extends('layouts.app')

@section('title', 'Bảng xếp hạng - APTIS')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">🏆 Bảng xếp hạng</h1>
    <p class="mt-2 text-gray-600">Top 20 điểm thi thử cao nhất theo kỹ năng</p>
</div>

{{-- Skill filter --}}
<div class="flex rounded-lg border border-gray-200 overflow-hidden bg-white shadow-sm mb-6 w-fit">
    @foreach(['reading' => '📖 Reading', 'listening' => '🎧 Listening', 'writing' => '✍️ Writing'] as $val => $label)
        <a href="{{ route('leaderboard.index', ['skill' => $val]) }}"
           class="px-4 py-2 text-sm font-medium {{ $skill === $val ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ !$loop->first ? 'border-l border-gray-200' : '' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- My best score --}}
@if($myBest)
    @php $myPct = (float)($myBest->score ?? 0); $myColor = $myPct>=80?'green':($myPct>=50?'amber':'red'); @endphp
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-6 flex items-center gap-4">
        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center font-black text-indigo-600">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-indigo-800">Điểm tốt nhất của bạn — {{ ucfirst($skill) }}</p>
            <p class="text-xs text-indigo-600">{{ $myBest->finished_at?->format('d/m/Y H:i') }}</p>
        </div>
        <span class="text-2xl font-black text-{{ $myColor }}-600">{{ number_format($myPct, 0) }}%</span>
    </div>
@endif

{{-- Leaderboard table --}}
@if($leaderboard->isEmpty())
    <x-alert type="info">Chưa có bài thi thử nào được hoàn thành cho kỹ năng này.</x-alert>
@else
    <x-card>
        <div class="overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">#</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Học sinh</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Điểm</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời gian</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày thi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($leaderboard as $rank => $mt)
                        @php
                            $isMe = $mt->user_id === auth()->id();
                            $score = (float)($mt->score ?? 0);
                            $scoreColor = $score >= 80 ? 'text-green-600' : ($score >= 50 ? 'text-amber-600' : 'text-red-500');
                            $medalClass = match($rank) {
                                0 => 'bg-amber-400 text-white',
                                1 => 'bg-gray-300 text-gray-700',
                                2 => 'bg-orange-400 text-white',
                                default => 'bg-gray-100 text-gray-600',
                            };
                            $medal = match($rank) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '' };
                        @endphp
                        <tr class="{{ $isMe ? 'bg-indigo-50/50 ring-1 ring-inset ring-indigo-200' : 'hover:bg-gray-50' }} transition-colors">
                            <td class="px-5 py-4">
                                <div class="w-8 h-8 rounded-full {{ $medalClass }} flex items-center justify-center text-sm font-bold">
                                    {{ $medal ?: $rank + 1 }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                                        {{ strtoupper(substr($mt->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $mt->user->name ?? 'N/A' }}
                                            @if($isMe) <span class="text-xs text-indigo-500 font-semibold">(Bạn)</span> @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xl font-black {{ $scoreColor }}">{{ number_format($score, 0) }}%</span>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500">
                                @if($mt->duration_seconds)
                                    {{ gmdate('i:s', $mt->duration_seconds) }}
                                @else —
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500">
                                {{ $mt->finished_at?->format('d/m/Y') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endif
@endsection
