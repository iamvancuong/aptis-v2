@extends('layouts.app')

@section('title', 'Sổ tay từ vựng')

@section('content')
@php
    $scopeParams = array_filter(['folder' => $filters['folder'] ?? null, 'type' => $filters['type'] ?? null]);
    $activeType = $filters['type'] ?? null;
    $activeFolder = isset($filters['folder']) ? (int) $filters['folder'] : null;
    $scopeTitle = $currentFolder?->name
        ?? ($activeType ? \App\Models\VocabularyItem::WORD_TYPES[$activeType] : 'Tất cả từ');

    $navClass = fn (bool $active) => $active
        ? 'bg-indigo-50 text-indigo-700 font-semibold'
        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
@endphp

<div class="max-w-6xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sổ tay từ vựng</h1>
            <p class="text-sm text-gray-500 mt-1">
                Những từ bạn đã lưu khi luyện tập. Bôi đen chữ trong bài đọc để tra nghĩa và lưu thêm.
            </p>
        </div>

        @if($totalAll > 0)
            {{-- Xuất PDF luyện viết: in đúng phạm vi + bộ lọc đang xem --}}
            <div x-data="{ open: false }" class="relative self-start lg:self-auto">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Xuất PDF luyện viết
                </button>

                <form x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.120ms
                      method="GET" action="{{ route('vocab.export.pdf') }}" @submit="setTimeout(() => open = false, 300)"
                      class="absolute right-0 lg:right-0 left-0 lg:left-auto mt-2 w-72 max-w-[calc(100vw-32px)] z-30 bg-white rounded-xl border border-gray-200 shadow-xl p-4 space-y-3">
                    @foreach(array_filter($filters) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div>
                        <p class="text-sm font-semibold text-gray-900">Phiếu luyện viết (PDF)</p>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                            In <strong>{{ $scopeTitle }}</strong>
                            @if(array_filter(\Illuminate\Support\Arr::only($filters, ['q', 'skill', 'status'])))
                                theo bộ lọc đang chọn
                            @endif
                            · {{ $items->total() }} từ. Mỗi từ có nghĩa, dòng chép mờ để tô và dòng trống để tự viết.
                        </p>
                    </div>

                    <label class="flex items-center justify-between gap-3 text-sm text-gray-700">
                        <span>Số dòng tự viết mỗi từ</span>
                        <select name="lines" class="text-sm py-1.5 pl-2 pr-7 border border-gray-300 rounded-lg">
                            @foreach([1, 2, 3, 4] as $n)
                                <option value="{{ $n }}" @selected($n === 2)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input type="hidden" name="self_test" value="0">
                        <input type="checkbox" name="self_test" value="1" checked
                               class="mt-0.5 w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Kèm trang tự kiểm tra <span class="text-gray-400">(nhìn nghĩa viết lại từ, có đáp án)</span></span>
                    </label>

                    <button type="submit" @disabled($items->total() === 0)
                            class="w-full py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition">
                        Tải PDF
                    </button>
                    @if($items->total() > (int) config('aptis.vocab.pdf_max_items', 200))
                        <p class="text-[11px] text-amber-700 leading-relaxed">
                            Mỗi tệp in tối đa {{ config('aptis.vocab.pdf_max_items', 200) }} từ — chọn một thư mục hoặc loại từ để in phần còn lại.
                        </p>
                    @endif
                </form>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-6 items-start">

        {{-- ─────────────── Thư mục ─────────────── --}}
        <aside class="bg-white rounded-xl border border-gray-200 p-3 space-y-4 lg:sticky lg:top-20">
            <div class="space-y-0.5">
                <a href="{{ route('vocab.index') }}"
                   class="flex items-center justify-between px-3 py-2 rounded-lg text-sm {{ $navClass(! $activeType && ! $activeFolder) }}">
                    <span>📚 Tất cả</span>
                    <span class="text-xs text-gray-400">{{ $totalAll }}</span>
                </a>
            </div>

            <div>
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Theo loại từ</p>
                <div class="space-y-0.5">
                    @foreach(\App\Models\VocabularyItem::WORD_TYPES as $type => $label)
                        @php
                            $count = $type === 'other'
                                ? ($typeCounts['other'] ?? 0) + ($typeCounts[''] ?? 0)
                                : ($typeCounts[$type] ?? 0);
                        @endphp
                        @continue($count === 0 && $activeType !== $type)
                        <a href="{{ route('vocab.index', ['type' => $type]) }}"
                           class="flex items-center justify-between px-3 py-1.5 rounded-lg text-sm {{ $navClass($activeType === $type) }}">
                            <span>{{ $label }}</span>
                            <span class="text-xs text-gray-400">{{ $count }}</span>
                        </a>
                    @endforeach
                    @if($typeCounts->isEmpty())
                        <p class="px-3 py-1 text-xs text-gray-400">Lưu từ đầu tiên để thấy thư mục tự động.</p>
                    @endif
                </div>
            </div>

            <div x-data="{ adding: {{ $errors->has('name') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between px-3 pb-1">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Thư mục của tôi</p>
                    <button type="button" @click="adding = !adding; $nextTick(() => $refs.folderName?.focus())"
                            class="text-indigo-600 hover:text-indigo-800 text-lg leading-none" title="Tạo thư mục">+</button>
                </div>

                <form x-show="adding" x-cloak method="POST" action="{{ route('vocab.folders.store') }}" class="px-1 pb-2 space-y-1">
                    @csrf
                    <div class="flex gap-1">
                        <input x-ref="folderName" type="text" name="name" maxlength="60" required value="{{ old('name') }}"
                               placeholder="Tên thư mục"
                               class="flex-1 min-w-0 text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500">
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-semibold">Tạo</button>
                    </div>
                    @error('name')<p class="text-xs text-red-600 px-1">{{ $message }}</p>@enderror
                </form>

                <div class="space-y-0.5">
                    @forelse($folders as $folder)
                        <a href="{{ route('vocab.index', ['folder' => $folder->id]) }}"
                           class="flex items-center justify-between px-3 py-1.5 rounded-lg text-sm {{ $navClass($activeFolder === $folder->id) }}">
                            <span class="truncate">📁 {{ $folder->name }}</span>
                            <span class="text-xs text-gray-400 shrink-0 ml-2">{{ $folder->items_count }}</span>
                        </a>
                    @empty
                        <p x-show="!adding" class="px-3 py-1 text-xs text-gray-400 leading-relaxed">
                            Chưa có thư mục. Bấm <strong>+</strong> để tạo, ví dụ “Chủ đề môi trường”.
                        </p>
                    @endforelse
                </div>
            </div>
        </aside>

        {{-- ─────────────── Nội dung ─────────────── --}}
        <div class="space-y-5 min-w-0">

            {{-- Tiêu đề phạm vi + ôn thư mục này --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0" x-data="{ renaming: false }">
                    <h2 x-show="!renaming" class="text-lg font-bold text-gray-900 truncate">{{ $scopeTitle }}</h2>

                    @if($currentFolder)
                        <form x-show="renaming" x-cloak method="POST" action="{{ route('vocab.folders.update', $currentFolder) }}" class="flex gap-1">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="name" value="{{ $currentFolder->name }}" maxlength="60" required
                                   class="text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500">
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-semibold">Lưu</button>
                            <button type="button" @click="renaming = false" class="px-2 text-xs text-gray-500">Huỷ</button>
                        </form>

                        <button x-show="!renaming" type="button" @click="renaming = true"
                                class="p-1 rounded text-gray-400 hover:text-indigo-600" title="Đổi tên thư mục">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <form x-show="!renaming" method="POST" action="{{ route('vocab.folders.destroy', $currentFolder) }}"
                              onsubmit="return confirm('Xoá thư mục này? Các từ bên trong vẫn được giữ lại trong sổ tay.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 rounded text-gray-400 hover:text-red-600" title="Xoá thư mục">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>

                @if($stats['due'] > 0)
                    <a href="{{ route('vocab.review', $scopeParams) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-semibold text-sm shadow-sm hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        {{ $scopeParams ? 'Ôn thư mục này' : 'Ôn tập ngay' }} ({{ $stats['due'] }} từ)
                    </a>
                @endif
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
                @foreach($scopeParams as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

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
                        @if($totalAll === 0)
                            Sổ tay còn trống
                        @elseif($currentFolder && $stats['total'] === 0)
                            Thư mục này chưa có từ nào
                        @else
                            Không có từ nào khớp bộ lọc
                        @endif
                    </h2>
                    @if($totalAll === 0)
                        <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                            Khi luyện tập, hãy <strong>bôi đen</strong> từ hoặc câu bạn chưa hiểu trong bài đọc,
                            bấm <strong>Tra từ</strong>, rồi bấm <strong>Lưu từ</strong>. Từ sẽ xuất hiện ở đây
                            kèm câu gốc trong bài để bạn nhớ theo ngữ cảnh.
                        </p>
                        <a href="{{ route('dashboard') }}" class="inline-block mt-5 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                            Bắt đầu luyện tập
                        </a>
                    @elseif($currentFolder && $stats['total'] === 0)
                        <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                            Chọn thư mục này ở mục <strong>Lưu vào</strong> khi tra từ, hoặc chuyển từ có sẵn sang đây
                            bằng ô <strong>📁</strong> dưới mỗi thẻ.
                        </p>
                    @else
                        <a href="{{ route('vocab.index', $scopeParams) }}" class="inline-block mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-800">
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
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-[11px] font-medium">{{ $item->part_of_speech ?: $item->wordTypeLabel() }}</span>
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

                            {{-- Thư mục + tiến độ ôn --}}
                            <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-100 mt-auto">
                                <form method="POST" action="{{ route('vocab.move', $item) }}" class="min-w-0">
                                    @csrf
                                    @method('PATCH')
                                    <select name="folder_id" onchange="this.form.submit()" title="Chuyển thư mục"
                                            class="max-w-[160px] text-[11px] py-1 pl-2 pr-6 border border-gray-200 rounded-md text-gray-500 bg-white focus:outline-none focus:border-indigo-500">
                                        <option value="">📁 Chưa xếp thư mục</option>
                                        @foreach($folders as $folder)
                                            <option value="{{ $folder->id }}" @selected($item->folder_id === $folder->id)>📁 {{ $folder->name }}</option>
                                        @endforeach
                                    </select>
                                </form>

                                <span class="text-[11px] shrink-0 {{ $item->isMastered() ? 'text-emerald-600 font-semibold' : 'text-gray-400' }}">
                                    {{ $item->statusLabel() }}
                                    @if($item->due_at === null || $item->due_at->isPast())
                                        · <span class="text-indigo-600 font-medium">cần ôn</span>
                                    @elseif($item->srs_state === 'review')
                                        · {{ $item->due_at->format('d/m') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div>{{ $items->links() }}</div>
            @endif
        </div>
    </div>
</div>

<style>[x-cloak] { display: none !important; }</style>
@endsection
