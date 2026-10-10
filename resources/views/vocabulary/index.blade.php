@extends('layouts.app')

@section('title', 'Sổ tay từ vựng - Milaedu')

@php
    $scopeParams = array_filter(['folder' => $filters['folder'] ?? null, 'type' => $filters['type'] ?? null]);
    $activeType = $filters['type'] ?? null;
    $activeFolder = isset($filters['folder']) ? (int) $filters['folder'] : null;
    $scopeTitle = $currentFolder?->name
        ?? ($activeType ? \App\Models\VocabularyItem::WORD_TYPES[$activeType] : 'Tất cả từ');

    // Một kiểu dòng cho cả 3 nhóm điều hướng bên trái.
    $navClass = fn (bool $active) => 'flex items-center justify-between px-3 py-2 rounded-lg text-sm ' . ($active
        ? 'bg-blue-50 text-blue-700 font-semibold'
        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900');
    $field = 'text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
@endphp

@section('content')
<x-ui.page-header title="Sổ tay từ vựng"
    subtitle="Những từ bạn đã lưu khi luyện tập. Bôi đen chữ trong bài đọc để tra nghĩa và lưu thêm."
    :crumbs="[['label' => 'Luyện tập', 'url' => route('dashboard')], ['label' => 'Từ vựng']]">
    @if($totalAll > 0)
        <x-slot:aside>
            {{-- Xuất PDF luyện viết: in đúng phạm vi + bộ lọc đang xem --}}
            <div x-data="{ open: false }" class="relative">
                <x-button variant="secondary" icon="download" x-on:click="open = !open">Xuất PDF luyện viết</x-button>

                <form x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.120ms
                      method="GET" action="{{ route('vocab.export.pdf') }}" @submit="setTimeout(() => open = false, 300)"
                      class="absolute left-0 lg:left-auto lg:right-0 mt-2 w-72 max-w-[calc(100vw-32px)] z-30 bg-white rounded-xl border border-gray-200 shadow-lg p-4 space-y-3">
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
                        <select name="lines" class="{{ $field }} py-1.5 pl-2 pr-7">
                            @foreach([1, 2, 3, 4] as $n)
                                <option value="{{ $n }}" @selected($n === 2)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input type="hidden" name="self_test" value="0">
                        <input type="checkbox" name="self_test" value="1" checked
                               class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>Kèm trang tự kiểm tra <span class="text-gray-400">(nhìn nghĩa viết lại từ, có đáp án)</span></span>
                    </label>

                    <x-button type="submit" class="w-full" :disabled="$items->total() === 0">Tải PDF</x-button>
                    @if($items->total() > (int) config('aptis.vocab.pdf_max_items', 200))
                        <p class="text-[11px] text-amber-700 leading-relaxed">
                            Mỗi tệp in tối đa {{ config('aptis.vocab.pdf_max_items', 200) }} từ — chọn một thư mục hoặc loại từ để in phần còn lại.
                        </p>
                    @endif
                </form>
            </div>
        </x-slot:aside>
    @endif
</x-ui.page-header>

<div class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-6 items-start">

    {{-- ─────────────── Thư mục ─────────────── --}}
    <x-ui.panel class="lg:sticky lg:top-6" :padded="false">
        <div class="p-3 space-y-4">
            <a href="{{ route('vocab.index') }}" class="{{ $navClass(! $activeType && ! $activeFolder) }}">
                <span>Tất cả</span>
                <span class="text-xs text-gray-400">{{ $totalAll }}</span>
            </a>

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
                        <a href="{{ route('vocab.index', ['type' => $type]) }}" class="{{ $navClass($activeType === $type) }}">
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
                            class="p-1 rounded-md text-blue-600 hover:bg-blue-50" title="Tạo thư mục" aria-label="Tạo thư mục">
                        <x-ui.icon name="plus" class="w-4 h-4" />
                    </button>
                </div>

                <form x-show="adding" x-cloak method="POST" action="{{ route('vocab.folders.store') }}" class="px-1 pb-2 space-y-1">
                    @csrf
                    <div class="flex gap-1">
                        <input x-ref="folderName" type="text" name="name" maxlength="60" required value="{{ old('name') }}"
                               placeholder="Tên thư mục" class="flex-1 min-w-0 px-2 py-1.5 {{ $field }}">
                        <x-button type="submit" size="sm">Tạo</x-button>
                    </div>
                    @error('name')<p class="text-xs text-red-600 px-1">{{ $message }}</p>@enderror
                </form>

                <div class="space-y-0.5">
                    @forelse($folders as $folder)
                        <a href="{{ route('vocab.index', ['folder' => $folder->id]) }}" class="{{ $navClass($activeFolder === $folder->id) }}">
                            <span class="truncate">{{ $folder->name }}</span>
                            <span class="text-xs text-gray-400 shrink-0 ml-2">{{ $folder->items_count }}</span>
                        </a>
                    @empty
                        <p x-show="!adding" class="px-3 py-1 text-xs text-gray-400 leading-relaxed">
                            Chưa có thư mục. Bấm <strong>+</strong> để tạo, ví dụ “Chủ đề môi trường”.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui.panel>

    {{-- ─────────────── Nội dung ─────────────── --}}
    <div class="space-y-5 min-w-0">

        {{-- Tiêu đề phạm vi + ôn thư mục này --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-1 min-w-0" x-data="{ renaming: false }">
                <h2 x-show="!renaming" class="text-lg font-semibold text-gray-900 truncate mr-1">{{ $scopeTitle }}</h2>

                @if($currentFolder)
                    <form x-show="renaming" x-cloak method="POST" action="{{ route('vocab.folders.update', $currentFolder) }}" class="flex gap-1">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="name" value="{{ $currentFolder->name }}" maxlength="60" required class="px-2 py-1.5 {{ $field }}">
                        <x-button type="submit" size="sm">Lưu</x-button>
                        <x-button variant="ghost" size="sm" x-on:click="renaming = false">Huỷ</x-button>
                    </form>

                    <button x-show="!renaming" type="button" @click="renaming = true"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50" title="Đổi tên thư mục" aria-label="Đổi tên thư mục">
                        <x-ui.icon name="edit" class="w-4 h-4" />
                    </button>
                    <form x-show="!renaming" method="POST" action="{{ route('vocab.folders.destroy', $currentFolder) }}"
                          onsubmit="return confirm('Xoá thư mục này? Các từ bên trong vẫn được giữ lại trong sổ tay.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50" title="Xoá thư mục" aria-label="Xoá thư mục">
                            <x-ui.icon name="trash" class="w-4 h-4" />
                        </button>
                    </form>
                @endif
            </div>

            @if($stats['due'] > 0)
                <x-button :href="route('vocab.review', $scopeParams)" icon="bolt">
                    {{ $scopeParams ? 'Ôn thư mục này' : 'Ôn tập ngay' }} ({{ $stats['due'] }} từ)
                </x-button>
            @endif
        </div>

        <div class="grid grid-cols-3 gap-3">
            <x-ui.stat :value="$stats['total']" label="Tổng số từ" />
            <x-ui.stat :value="$stats['due']" label="Đến hạn ôn" tone="blue" />
            <x-ui.stat :value="$stats['mastered']" label="Đã thuộc" tone="green" />
        </div>

        {{-- Bộ lọc --}}
        <form method="GET" action="{{ route('vocab.index') }}"
              class="bg-white rounded-2xl border border-gray-200 p-4 flex flex-col sm:flex-row gap-3">
            @foreach($scopeParams as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div class="relative flex-1">
                <x-ui.icon name="search" class="w-4 h-4 text-gray-400 absolute left-3 top-3" />
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm theo từ hoặc nghĩa…"
                       class="w-full pl-9 pr-3 py-2.5 {{ $field }}">
            </div>

            <select name="skill" class="py-2.5 px-3 {{ $field }}">
                <option value="">Tất cả kỹ năng</option>
                @foreach(\App\Support\SkillMeta::ORDER as $value)
                    <option value="{{ $value }}" @selected(($filters['skill'] ?? '') === $value)>{{ \App\Support\SkillMeta::get($value)['name'] }}</option>
                @endforeach
            </select>

            <select name="status" class="py-2.5 px-3 {{ $field }}">
                <option value="">Mọi trạng thái</option>
                <option value="due" @selected(($filters['status'] ?? '') === 'due')>Đến hạn ôn</option>
                <option value="learning" @selected(($filters['status'] ?? '') === 'learning')>Đang học</option>
                <option value="mastered" @selected(($filters['status'] ?? '') === 'mastered')>Đã thuộc</option>
            </select>

            <x-button type="submit" variant="secondary">Lọc</x-button>
        </form>

        {{-- Danh sách --}}
        @if($items->isEmpty())
            @if($totalAll === 0)
                <x-ui.empty-state title="Sổ tay còn trống" icon="book">
                    Khi luyện tập, hãy <strong>bôi đen</strong> từ hoặc câu bạn chưa hiểu trong bài đọc, bấm <strong>Tra từ</strong>,
                    rồi bấm <strong>Lưu từ</strong>. Từ sẽ xuất hiện ở đây kèm câu gốc để bạn nhớ theo ngữ cảnh.
                    <span class="block mt-4"><x-button :href="route('dashboard')">Bắt đầu luyện tập</x-button></span>
                </x-ui.empty-state>
            @elseif($currentFolder && $stats['total'] === 0)
                <x-ui.empty-state title="Thư mục này chưa có từ nào" icon="list">
                    Chọn thư mục này ở mục <strong>Lưu vào</strong> khi tra từ, hoặc chuyển từ có sẵn sang đây bằng ô chọn thư mục dưới mỗi thẻ.
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Không có từ nào khớp bộ lọc" icon="search">
                    <a href="{{ route('vocab.index', $scopeParams) }}" class="text-blue-600 hover:text-blue-700 font-medium">Xoá bộ lọc</a>
                </x-ui.empty-state>
            @endif
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                @foreach($items as $item)
                    <x-ui.tile x-data="{ editing: false }" class="gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <h3 class="font-semibold text-gray-900 break-words">{{ $item->term }}</h3>
                                    @if($item->phonetic)
                                        <span class="text-xs text-gray-400">{{ $item->phonetic }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 text-[11px] font-medium">{{ $item->part_of_speech ?: $item->wordTypeLabel() }}</span>
                                    @if($item->cefr)
                                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[11px] font-semibold">{{ $item->cefr }}</span>
                                    @endif
                                    @if($item->source_skill)
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-medium {{ \App\Support\SkillMeta::get($item->source_skill)['tone'] }}">
                                            {{ \App\Support\SkillMeta::get($item->source_skill)['name'] }}@if($item->source_part) P{{ $item->source_part }}@endif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" @click="editing = !editing"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Sửa nghĩa" aria-label="Sửa nghĩa">
                                    <x-ui.icon name="edit" class="w-4 h-4" />
                                </button>
                                <form method="POST" action="{{ route('vocab.destroy', $item) }}"
                                      onsubmit="return confirm('Xoá từ &quot;{{ $item->term }}&quot; khỏi sổ tay?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Xoá từ" aria-label="Xoá từ">
                                        <x-ui.icon name="trash" class="w-4 h-4" />
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
                            <textarea name="meaning" rows="2" required class="w-full px-3 py-2 {{ $field }}">{{ $item->meaning }}</textarea>
                            <textarea name="example" rows="2" placeholder="Câu ví dụ (không bắt buộc)" class="w-full px-3 py-2 {{ $field }}">{{ $item->example }}</textarea>
                            <div class="flex items-center gap-2">
                                <x-button type="submit" size="sm">Lưu</x-button>
                                <x-button variant="secondary" size="sm" x-on:click="editing = false">Huỷ</x-button>
                            </div>
                        </form>

                        {{-- Thư mục + tiến độ ôn --}}
                        <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-100 mt-auto">
                            <form method="POST" action="{{ route('vocab.move', $item) }}" class="min-w-0">
                                @csrf
                                @method('PATCH')
                                <select name="folder_id" onchange="this.form.submit()" title="Chuyển thư mục"
                                        class="max-w-[160px] text-[11px] py-1 pl-2 pr-6 border border-gray-200 rounded-md text-gray-500 bg-white focus:outline-none focus:border-blue-500">
                                    <option value="">Chưa xếp thư mục</option>
                                    @foreach($folders as $folder)
                                        <option value="{{ $folder->id }}" @selected($item->folder_id === $folder->id)>{{ $folder->name }}</option>
                                    @endforeach
                                </select>
                            </form>

                            <span class="text-[11px] shrink-0 {{ $item->isMastered() ? 'text-emerald-600 font-semibold' : 'text-gray-400' }}">
                                {{ $item->statusLabel() }}
                                @if($item->due_at === null || $item->due_at->isPast())
                                    · <span class="text-blue-600 font-medium">cần ôn</span>
                                @elseif($item->srs_state === 'review')
                                    · {{ $item->due_at->format('d/m') }}
                                @else
                                    {{-- Đang học: hẹn tính bằng phút/giờ nên phải ghi giờ --}}
                                    · ôn lúc {{ $item->due_at->format($item->due_at->isToday() ? 'H:i' : 'H:i d/m') }}
                                @endif
                            </span>
                        </div>
                    </x-ui.tile>
                @endforeach
            </div>

            @if($items->hasPages())
                <div>{{ $items->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
