@props([
    'data' => null,
    'perPageOptions' => [10, 20, 50],
    'perPageDefault' => 10
])

@php
    $currentPerPage = request()->integer('per_page', $perPageDefault);
@endphp

<div class="bg-white rounded-lg shadow overflow-hidden">
    <!-- Table wrapper for mobile horizontal scroll -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            {{$slot}}
        </table>
    </div>
    
    <!-- Pagination controls -->
    <div class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
        <!-- Per page selector -->
        @if($data && $data->hasPages())
            <x-datatable.per-page-selector :options="$perPageOptions" :current="$currentPerPage" />
        @endif
        
        <!-- Pagination links -->
        @if($data && $data->hasPages())
            <div class="flex-1 flex justify-center md:justify-end">
                <x-datatable.pagination :paginator="$data" />
            </div>
        @endif
    </div>
</div>
