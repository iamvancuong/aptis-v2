@extends('layouts.app')

@section('title', ucfirst($skill) . ' - Thi thử')

@section('content')
<div class="mb-8">
    <a href="{{ route('skills.show', $skill) }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Quay lại {{ ucfirst($skill) }}
    </a>
    <h1 class="text-3xl font-bold text-gray-900">Thi thử {{ ucfirst($skill) }}</h1>
    <p class="mt-2 text-gray-600">Làm bài thi đầy đủ tất cả các Part theo đúng cấu trúc APTIS</p>
</div>

<div class="max-w-2xl mx-auto">
    <x-card>
        {{-- Exam Info --}}
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center
                @if($skill === 'reading') bg-blue-100 @elseif($skill === 'listening') bg-green-100 @elseif($skill === 'speaking') bg-orange-100 @else bg-purple-100 @endif">
                @if($skill === 'reading')
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                @elseif($skill === 'listening')
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                    </svg>
                @elseif($skill === 'speaking')
                    <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                @else
                    <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                @endif
            </div>

            <h2 class="text-2xl font-bold text-gray-800">{{ ucfirst($skill) }} Full Test</h2>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-gray-800">{{ count($sections) }}</p>
                <p class="text-sm text-gray-500">Sections</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-gray-800">{{ $duration }}</p>
                <p class="text-sm text-gray-500">Phút</p>
            </div>
        </div>

        {{-- Section Breakdown --}}
        <div class="mb-8">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Cấu trúc bài thi</h3>
            <div class="space-y-2">
                @foreach($sections as $index => $part)
                    <div class="flex items-center gap-3 px-4 py-2 rounded-lg bg-gray-50">
                        <span class="w-8 h-8 rounded-full bg-white border-2 border-gray-200 flex items-center justify-center text-sm font-bold text-gray-600">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-sm font-medium text-gray-700">Part {{ $part }}</span>
                        @php
                            $partInfo = $partCounts[$part] ?? null;
                        @endphp
                        @if($partInfo && !$partInfo['enough'])
                            <span class="ml-auto text-xs text-red-500">⚠ Thiếu bộ đề</span>
                        @else
                            <span class="ml-auto text-xs text-green-500">✓ Sẵn sàng</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Start Button --}}
        @if($canStart)
            <form method="POST" action="{{ route('mock-test.start') }}">
                @csrf
                <input type="hidden" name="skill" value="{{ $skill }}">
                
                @if(($skill === 'writing' || $skill === 'speaking') && isset($writingSets) && $writingSets->count() > 0)
                    <div class="mb-6">
                        <label for="set_id" class="block text-sm font-medium text-gray-700 mb-2 mt-4">Chọn bộ đề {{ ucfirst($skill) }}</label>
                        <select name="set_id" id="set_id" class="block w-full border border-gray-300 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3 px-4 bg-white hover:border-gray-400 transition-colors cursor-pointer" required>
                            @foreach($writingSets as $set)
                                <option value="{{ $set->id }}">{{ $set->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r
                    @if($skill === 'reading') from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700
                    @elseif($skill === 'listening') from-green-500 to-green-600 hover:from-green-600 hover:to-green-700
                    @elseif($skill === 'speaking') from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700
                    @else from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700
                    @endif
                    text-white font-semibold rounded-xl shadow-lg transition-all text-lg">
                    🚀 Bắt đầu thi thử
                </button>
            </form>
        @else
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <p class="text-red-600 font-medium">Không đủ bộ đề để thi thử</p>
                <p class="text-red-400 text-sm mt-1">Vui lòng liên hệ admin để thêm câu hỏi</p>
            </div>
        @endif
    </x-card>
</div>
@endsection
