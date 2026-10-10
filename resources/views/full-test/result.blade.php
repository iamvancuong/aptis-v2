@extends('layouts.app')

@section('title', 'Kết quả Full Test ' . $fullTest->code() . ' - Milaedu')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-ui.page-header class="!mb-0" :title="'Kết quả Full Test ' . $fullTest->code()"
        :subtitle="'Thi ngày ' . $fullTest->started_at?->format('d/m/Y')"
        :crumbs="[['label' => 'Full Test', 'url' => route('full-test.index')], ['label' => $fullTest->code()]]" />

    @php $fullTest->loadMissing('user'); @endphp

    @if($aim)
        @include('full-test._celebration')
    @endif

    @include('full-test._report')
</div>
@endsection
