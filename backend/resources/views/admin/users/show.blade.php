@extends('layouts.admin')

@section('title', 'User Detail')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Back to Users
    </a>
    <h1 class="text-2xl font-bold text-gray-900">User Detail</h1>
</div>

@if(session('success'))
    <x-alert type="success" class="mb-4">
        {{ session('success') }}
    </x-alert>
@endif

@if(session('error'))
    <x-alert type="danger" class="mb-4">
        {{ session('error') }}
    </x-alert>
@endif

<!-- User Information Card -->
<x-card class="mb-6">
    <h2 class="text-xl font-semibold mb-4">User Information</h2>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-600">Name</p>
            <p class="font-medium">{{ $user->name }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Email</p>
            <p class="font-medium">{{ $user->email }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Role</p>
            <x-badge :variant="$user->role === 'admin' ? 'warning' : 'default'">
                {{ ucfirst($user->role) }}
            </x-badge>
        </div>
        <div>
            <p class="text-sm text-gray-600">Status</p>
            <x-badge :variant="$user->status === 'active' ? 'success' : 'danger'">
                {{ ucfirst($user->status) }}
            </x-badge>
        </div>
        <div>
            <p class="text-sm text-gray-600">Violations</p>
            <p class="font-medium {{ $user->violation_count >= 3 ? 'text-red-600' : '' }}">
                {{ $user->violation_count }}/3
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Max Devices</p>
            <p class="font-medium">{{ $user->max_devices }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Registered</p>
            <p class="font-medium">{{ $user->created_at->format('Y-m-d H:i') }}</p>
        </div>
    </div>

    <div class="mt-6 flex gap-3">
        @if($user->violation_count > 0)
            <form action="{{ route('admin.users.reset-violations', $user) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Reset Violations
                </button>
            </form>
        @endif
        
        @if($user->status === 'active' && !$user->isAdmin())
            <form action="{{ route('admin.users.block', $user) }}" method="POST" class="inline" onsubmit="return confirm('Block this user? All sessions will be terminated.')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Block User
                </button>
            </form>
        @elseif($user->status === 'blocked')
            <form action="{{ route('admin.users.unblock', $user) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Unblock User
                </button>
            </form>
        @endif
    </div>
</x-card>

<!-- Login Sessions Card -->
<x-card class="mb-6">
    <h2 class="text-xl font-semibold mb-4">Login Sessions ({{ $user->loginSessions->count() }})</h2>
    @if($user->loginSessions->count() > 0)
        <x-table>
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User Agent</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Active</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($user->loginSessions as $session)
                    <tr>
                        <td class="px-6 py-4 text-sm font-mono">{{ substr($session->device_id, 0, 12) }}...</td>
                        <td class="px-6 py-4 text-sm">{{ $session->ip_address }}</td>
                        <td class="px-6 py-4 text-sm">{{ Str::limit($session->user_agent, 60) }}</td>
                        <td class="px-6 py-4 text-sm">
                            {{ $session->last_active_at ? $session->last_active_at->diffForHumans() : 'Never' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @else
        <p class="text-gray-500 text-center py-6">No active sessions</p>
    @endif
</x-card>

<!-- Attempt History Card -->
<x-card>
    <h2 class="text-xl font-semibold mb-4">Attempt History ({{ $user->attempts->count() }})</h2>
    @if($user->attempts->count() > 0)
        <x-table>
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quiz</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Set</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($user->attempts as $attempt)
                    <tr>
                        <td class="px-6 py-4 text-sm">{{ $attempt->quiz->title ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $attempt->set->title ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm font-semibold">{{ $attempt->score }}</td>
                        <td class="px-6 py-4 text-sm">{{ $attempt->total_questions }}</td>
                        <td class="px-6 py-4 text-sm">
                            @php
                                $percentage = $attempt->total_questions > 0 
                                    ? round(($attempt->score / $attempt->total_questions) * 100, 1) 
                                    : 0;
                            @endphp
                            <span class="{{ $percentage >= 70 ? 'text-green-600' : ($percentage >= 50 ? 'text-yellow-600' : 'text-red-600') }} font-medium">
                                {{ $percentage }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $attempt->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @else
        <p class="text-gray-500 text-center py-6">No attempts yet</p>
    @endif
</x-card>
@endsection
