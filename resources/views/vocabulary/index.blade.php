@extends('layouts.app')

@section('title', 'Sổ tay từ vựng')

@section('content')
@php
    $maxBox = \App\Models\VocabularyItem::MAX_BOX;
@endphp

<div class="max-w-6xl mx-auto space-y-6">

    {{-- Header + thống kê --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sổ tay từ vựng</h1>
            <p class="text-sm text-gray-500 mt-1">
                Những từ bạn đã lưu khi luyện tập. Bôi đen chữ trong bài đọc để tra nghĩa và lưu thêm.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($stats['due'] > 0)
                <a href="{{ route('vocab.review') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-semibold text-sm shadow-sm hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Ôn tập ngay ({{ $stats['due'] }} từ)
                </a>
            @endif

            @if($stats['total'] > 0)
                <a href="{{ route('vocab.export') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Xuất file
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-2xl font-black text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 font-medium mt-0.5">Tổng số từ</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-2xl font-black text-indigo-600">{{ $stats['due'] }}</p>
            <p class="text-xs text-gray-500 font-medium mt-0.5">Đến hạn ôn</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-2xl font-black text-emerald-600">{{ $stats['mastered'] }}</p>
            <p class="text-xs text-gray-500 font-medium mt-0.5">Đã thuộc</p>
        </div>
    </div>

    {{-- Bộ lọc --}}
    <form method="GET" action="{{ route('vocab.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Tìm theo từ hoặc nghĩa…"
                   class="w-full text-sm pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
        </div>

        <select name="skill" class="text-sm py-2.5 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500">
            <option value="">Tất cả kỹ năng</option>
            @foreach(['reading' => 'Reading', 'listening' => 'Listening', 'writing' => 'Writing', 'speaking' => 'Speaking', 'grammar' => 'Grammar'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['skill'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="status" class="text-sm py-2.5 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500">
            <option value="">Mọi trạng thái</option>
            <option value="due" @selected(($filters['status'] ?? '') === 'due')>Đến hạn ôn</option>
            <option value="learning" @selected(($filters['status'] ?? '') === 'learning')>Đang học</option>
            <option value="mastered" @selected(($filters['status'] ?? '') === 'mastered')>Đã thuộc</option>
        </select>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 transition">
            Lọc
        </button>
    </form>

    {{-- Danh sách --}}
    @if($items->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-gray-300 py-16 px-6 text-center">
            <div class="text-4xl mb-3">📒</div>
            <h2 class="font-bold text-gray-900 mb-1">
                {{ $stats['total'] === 0 ? 'Sổ tay còn trống' : 'Không có từ nào khớp bộ lọc' }}
            </h2>
            @if($stats['total'] === 0)
                <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                    Khi luyện tập, hãy <strong>bôi đen</strong> từ hoặc câu bạn chưa hiểu trong bài đọc,
                    bấm <strong>Tra từ</strong>, rồi bấm <strong>Lưu từ</strong>. Từ sẽ xuất hiện ở đây
                    kèm câu gốc trong bài để bạn nhớ theo ngữ cảnh.
                </p>
                <a href="{{ route('dashboard') }}" class="inline-block mt-5 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                    Bắt đầu luyện tập
                </a>
            @else
                <a href="{{ route('vocab.index') }}" class="inline-block mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Xoá bộ lọc
                </a>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($items as $item)
                <div x-data="{ editing: false }"
                     class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col gap-3 hover:border-indigo-200 transition">

                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-baseline gap-2 flex-wrap">
                                <h3 class="font-bold text-gray-900 break-words">{{ $item->term }}</h3>
                                @if($item->phonetic)
                                    <span class="text-xs text-gray-400">{{ $item->phonetic }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                @if($item->part_of_speech)
                                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-[11px] font-medium">{{ $item->part_of_speech }}</span>
                                @endif
                                @if($item->cefr)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-bold">{{ $item->cefr }}</span>
                                @endif
                                @if($item->source_skill)
                                    <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[11px] font-medium">
                                        {{ ucfirst($item->source_skill) }}@if($item->source_part) P{{ $item->source_part }}@endif
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" @click="editing = !editing"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Sửa nghĩa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            <form method="POST" action="{{ route('vocab.destroy', $item) }}"
                                  onsubmit="return confirm('Xoá từ &quot;{{ $item->term }}&quot; khỏi sổ tay?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Xoá từ">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Xem --}}
                    <div x-show="!editing" class="space-y-2">
                        <p class="text-[15px] text-gray-900 font-medium leading-relaxed">{{ $item->meaning }}</p>

                        @if($item->example)
                            <p class="text-sm text-gray-600 italic leading-relaxed">{{ $item->example }}</p>
                        @endif

                        @if($item->context_sentence)
                            <p class="text-xs text-gray-500 bg-gray-50 border-l-2 border-gray-200 pl-3 py-1.5 leading-relaxed">
                                {{ \Illuminate\Support\Str::limit($item->context_sentence, 160) }}
                            </p>
                        @endif
                    </div>

                    {{-- Sửa --}}
                    <form x-show="editing" x-cloak method="POST" action="{{ route('vocab.update', $item) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <textarea name="meaning" rows="2" required
                                  class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500">{{ $item->meaning }}</textarea>
                        <textarea name="example" rows="2" placeholder="Câu ví dụ (không bắt buộc)"
                                  class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500">{{ $item->example }}</textarea>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">Lưu</button>
                            <button type="button" @click="editing = false" class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 text-xs font-medium hover:bg-gray-50">Huỷ</button>
                        </div>
                    </form>

                    {{-- Tiến độ ôn --}}
                    <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-100 mt-auto">
                        <div class="flex items-center gap-1" title="Mức độ thuộc: hộp {{ $item->box }}/{{ $maxBox }}">
                            @for($i = 1; $i <= $maxBox; $i++)
                                <span class="w-5 h-1.5 rounded-full {{ $i <= $item->box ? 'bg-emerald-500' : 'bg-gray-200' }}"></span>
                            @endfor
                        </div>

                        <span class="text-[11px] text-gray-400">
                            @if($item->isMastered())
                                Đã thuộc
                            @elseif($item->due_at === null || $item->due_at->isPast())
                                Cần ôn hôm nay
                            @else
                                Ôn lại {{ $item->due_at->format('d/m') }}
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $items->links() }}</div>
    @endif
</div>

<style>[x-cloak] { display: none !important; }</style>
@endsection
