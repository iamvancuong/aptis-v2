@extends('layouts.app')

@section('title', 'Kết quả Full Test ' . $fullTest->code() . ' - Milaedu')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('full-test.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm">← Full Test</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Kết quả Full Test {{ $fullTest->code() }}</h1>
        <p class="text-sm text-gray-500 mt-1">Thi ngày {{ $fullTest->started_at?->format('d/m/Y') }}</p>
    </div>

    @php $fullTest->loadMissing('user'); @endphp

    @if($aim)
        @include('full-test._celebration')
    @endif

    @include('full-test._report')
</div>
@endsection
