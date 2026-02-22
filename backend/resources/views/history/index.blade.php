@extends('layouts.app')

@section('title', 'Lịch sử làm bài - APTIS Practice')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Lịch sử làm bài</h1>
    <p class="mt-2 text-gray-600">Xem lại các bài thi và luyện tập của bạn</p>
</div>

@if($attempts->isEmpty())
    <x-alert type="info">
        Bạn chưa có bài làm nào. Hãy bắt đầu luyện tập!
    </x-alert>
    <x-button href="{{ route('dashboard') }}" class="mt-4">
        Về Dashboard
    </x-button>
@else
    <x-card>
        <x-table>
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kỹ năng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Điểm</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời gian</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày làm</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($attempts as $attempt)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="capitalize">{{ $attempt->skill }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$attempt->mode === 'mock_test' ? 'warning' : 'default'">
                                {{ $attempt->mode === 'mock_test' ? 'Thi thử' : 'Luyện tập' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($attempt->score !== null)
                                <span class="font-semibold">{{ $attempt->score }}</span>
                            @else
                                <span class="text-gray-400">Chưa chấm</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($attempt->duration_seconds)
                                {{ gmdate('H:i:s', $attempt->duration_seconds) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $attempt->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @if($attempt->mock_test_id)
                                <a href="{{ route('mock-test.result', $attempt->mock_test_id) }}" class="text-blue-600 hover:text-blue-700">
                                    Xem kết quả
                                </a>
                            @else
                                <a href="{{ route('history.show', $attempt->id) }}" class="text-blue-600 hover:text-blue-700">
                                    Xem chi tiết
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>

    <div class="mt-4">
        {{ $attempts->links() }}
    </div>
@endif
@endsection
