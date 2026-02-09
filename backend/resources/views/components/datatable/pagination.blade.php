@props(['paginator'])

@if($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center space-x-2">
        {{-- Previous Page Link --}}
        @if($paginator->onFirstPage())
            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded cursor-not-allowed">
                ← Previous
            </span>
        @else
            <a href="{{$paginator->previousPageUrl()}}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                ← Previous
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="px-3 py-2 text-sm text-white bg-blue-600 rounded">
                    {{$page}}
                </span>
            @else
                <a href="{{$url}}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    {{$page}}
                </a>
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if($paginator->hasMorePages())
            <a href="{{$paginator->nextPageUrl()}}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                Next →
            </a>
        @else
            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded cursor-not-allowed">
                Next →
            </span>
        @endif
    </nav>
@endif
