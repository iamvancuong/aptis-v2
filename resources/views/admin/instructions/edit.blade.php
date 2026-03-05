@extends('layouts.admin')

@section('title', 'Sửa Hướng dẫn')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.instructions.index') }}" class="text-gray-500 hover:text-indigo-600 transition-colors">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Sửa Hướng dẫn</h1>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <form action="{{ route('admin.instructions.update', $instruction) }}" method="POST" enctype="multipart/form-data" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 space-y-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Tiêu đề <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title', $instruction->title) }}" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        required>
                    @error('title')
                        <p class="text-red-500 text-sm mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Content (Rich Text) -->
                <div>
                    <label for="content" class="block text-sm font-semibold text-gray-700 mb-2">Nội dung bài viết <span class="text-gray-400 font-normal">(Tùy chọn)</span></label>
                    <div class="prose max-w-none">
                        <textarea id="content" name="content" rows="15" class="w-full editor-content">{{ old('content', $instruction->content) }}</textarea>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Bạn có thể soạn thảo nội dung hướng dẫn chi tiết tại đây.</p>
                    @error('content')
                        <p class="text-red-500 text-sm mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="space-y-6">
                <!-- Video Settings -->
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200" x-data="{ videoType: '{{ $instruction->video_path ? 'upload' : 'url' }}' }">
                    <label class="block text-sm font-semibold text-gray-700 mb-4 border-b border-gray-200 pb-2">Nguồn Video Hướng Dẫn</label>
                    
                    <div class="flex gap-4 mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="video_source_type" value="url" x-model="videoType" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 font-medium">Dùng Link (Khuyên dùng)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="video_source_type" value="upload" x-model="videoType" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 font-medium">Tải file lên</span>
                        </label>
                    </div>

                    <!-- URL Input -->
                    <div x-show="videoType === 'url'" x-transition class="space-y-2">
                        <label for="video_url" class="block text-sm font-medium text-gray-700">Đường dẫn YouTube / Google Drive</label>
                        <input type="url" id="video_url" name="video_url" value="{{ old('video_url', $instruction->video_url) }}" placeholder="https://www.youtube.com/watch?v=..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        <p class="mt-1 text-xs text-indigo-600 max-w-sm">Hệ thống sẽ tự động tối ưu và nhúng video cực nhanh mà không gây nặng máy chủ.</p>
                        
                        @if($instruction->video_url)
                        <div class="mt-3 bg-white p-3 rounded-lg border border-gray-200 shadow-sm relative pt-8">
                            <span class="absolute top-2 left-3 text-xs font-semibold text-gray-500">Video Link hiện tại:</span>
                            <div class="rounded overflow-hidden bg-black aspect-video flex items-center justify-center">
                                @if(Str::contains($instruction->video_url, 'youtube.com') || Str::contains($instruction->video_url, 'youtu.be'))
                                    @php
                                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $instruction->video_url, $matches);
                                        $youtubeId = $matches[1] ?? '';
                                    @endphp
                                    @if($youtubeId)
                                        <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $youtubeId }}" frameborder="0" allowfullscreen></iframe>
                                    @endif
                                @elseif(Str::contains($instruction->video_url, 'drive.google.com'))
                                    @php
                                        $driveUrl = preg_replace('/\/view.*/', '/preview', $instruction->video_url);
                                    @endphp
                                    <iframe class="w-full h-full" src="{{ $driveUrl }}" frameborder="0" allowfullscreen></iframe>
                                @else
                                    <span class="text-white text-sm">Không thể xem trước link này</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        
                        @error('video_url')
                            <p class="text-red-500 text-sm mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- File Input -->
                    <div x-show="videoType === 'upload'" x-transition class="space-y-2 mt-2" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hoặc tải file lên từ máy tính</label>
                        
                        @if($instruction->video_path)
                        <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <p class="text-xs font-semibold text-gray-500 mb-2">File Video hiện tại đang lưu:</p>
                            <div class="rounded overflow-hidden bg-black aspect-video flex items-center justify-center">
                                <video controls class="w-full h-full object-contain">
                                    <source src="{{ asset('storage/' . $instruction->video_path) }}">
                                </video>
                            </div>
                        </div>
                        @endif

                        <input type="file" id="video_file" name="video_file" accept="video/mp4,video/x-m4v,video/*"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-colors">
                        <p class="mt-2 text-xs text-gray-500">Hỗ trợ MP4, MOV. Dung lượng tối đa: 200MB. (Tải lên video mới sẽ ghi đè video cũ)</p>
                        @error('video_file')
                            <p class="text-red-500 text-sm mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Status & Order -->
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 space-y-4">
                    <div>
                        <label for="sort_order" class="block text-sm font-semibold text-gray-700 mb-2">Vị trí (Thứ tự hiển thị)</label>
                        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $instruction->sort_order) }}" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-4">
                            <input type="checkbox" name="is_published" value="1" class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" {{ old('is_published', $instruction->is_published) ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-gray-700">Công khai (Hiển thị)</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end gap-3">
            <a href="{{ route('admin.instructions.index') }}" class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                Hủy bỏ
            </a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<style>
    .ck-editor__editable_inline {
        min-height: 400px;
    }
</style>
<script src="https://cdn.ckeditor.com/ckeditor5/38.1.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('.editor-content');
        textareas.forEach(textarea => {
            ClassicEditor
                .create(textarea, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo']
                })
                .catch(error => {
                    console.error(error);
                });
        });
    });
</script>
@endpush
