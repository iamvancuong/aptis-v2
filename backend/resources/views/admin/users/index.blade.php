@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
    <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        + Create User
    </a>
</div>


<!-- Filter & Actions Card -->
<x-card class="mb-6">
    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <form method="GET" class="flex-1 flex flex-col md:flex-row gap-4">
            <input 
                type="text" 
                name="search" 
                placeholder="Search name or email..." 
                value="{{ request('search') }}"
                class="flex- px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">All Roles</option>
                <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
            </select>
            <select name="expiration" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">All Expiration</option>
                <option value="expired" {{ request('expiration') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="warning" {{ request('expiration') === 'warning' ? 'selected' : '' }}>Warning</option>
                <option value="active" {{ request('expiration') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="never" {{request('expiration') === 'never' ? 'selected' : '' }}>Never</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 whitespace-nowrap">
                Filter
            </button>
            @if(request()->hasAny(['search', 'role', 'status', 'expiration']))
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 whitespace-nowrap">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.users.export', request()->query()) }}" 
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
            📥 Export Excel
        </a>
        <button onclick="document.getElementById('importModal').classList.remove('hidden')" 
                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
            📤 Import Excel
        </button>
        <a href="{{ route('admin.users.template') }}" 
           class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm">
            📋 Download Template
        </a>
    </div>
</x-card>

<!-- Users Datatable -->
<x-datatable :data="$users" :per-page-options="[10, 20, 50]">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Violations</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiration</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quick Extend</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($users as $user)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                <td class="px-6 py-4 text-sm">{{ $user->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $user->email }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-badge :variant="$user->role === 'admin' ? 'warning' : 'default'">
                        {{ ucfirst($user->role) }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-badge :variant="$user->status === 'active' ? 'success' : 'danger'">
                        {{ ucfirst($user->status) }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span class="{{ $user->violation_count >= 3 ? 'text-red-600 font-bold' : '' }}">
                        {{ $user->violation_count }}/3
                    </span>
                </td>
                <td class="px-6 py-4">
                    @php
                        $status = $user->expirationStatus();
                        $badgeColors = [
                            'expired' => 'bg-red-100 text-red-800',
                            'warning' => 'bg-yellow-100 text-yellow-800',
                            'active' => 'bg-green-100 text-green-800',
                            'never' => 'bg-gray-100 text-gray-600'
                        ];
                    @endphp
                    <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors[$status] }}">
                        @if($status === 'expired') Expired
                        @elseif($status === 'warning') {{ $user->daysUntilExpiration() }} days
                        @elseif($status === 'active') {{ $user->expires_at->format('M d, Y') }}
                        @else Never
                        @endif
                    </span>
                </td>
                
                <!-- Quick Extend Column -->
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                    @if($user->expires_at)
                        @if($user->isExpired())
                            <form action="{{ route('admin.users.extend-expiration', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Renew this user for 30 days?')">
                                @csrf
                                <input type="hidden" name="days" value="30">
                                <button type="submit" class="inline-flex items-center px-3 py-1 bg-teal-100 text-teal-700 rounded-md hover:bg-teal-200 font-medium text-xs">
                                    Renew
                                </button>
                            </form>
                        @else
                            <div class="inline-flex gap-1">
                                <form action="{{ route('admin.users.extend-expiration', $user) }}" method="POST" class="inline-block">
                                    @csrf
                                    <input type="hidden" name="days" value="30">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 bg-amber-100 text-amber-700 rounded hover:bg-amber-200 font-medium text-xs">+30d</button>
                                </form>
                                <form action="{{ route('admin.users.extend-expiration', $user) }}" method="POST" class="inline-block">
                                    @csrf
                                    <input type="hidden" name="days" value="90">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 bg-orange-100 text-orange-700 rounded hover:bg-orange-200 font-medium text-xs">+90d</button>
                                </form>
                            </div>
                        @endif
                    @else
                        <span class="text-gray-400 text-xs">-</span>
                    @endif
                </td>
                
                <!-- Actions Column -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                    <!-- View -->
                    <a href="{{ route('admin.users.show', $user) }}" 
                        class="inline-flex items-center px-3 py-1 bg-sky-100 text-sky-700 rounded-md hover:bg-sky-200 font-medium text-xs">
                        View
                    </a>
                    
                    <!-- Edit -->
                    <a href="{{ route('admin.users.edit', $user) }}" 
                        class="inline-flex items-center px-3 py-1 bg-violet-100 text-violet-700 rounded-md hover:bg-violet-200 font-medium text-xs">
                        Edit
                    </a>
                    
                    <!-- Block/Unblock -->
                    @if($user->status === 'active' && !$user->isAdmin())
                        <form action="{{ route('admin.users.block', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Block this user?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 font-medium text-xs">
                                Block
                            </button>
                        </form>
                    @elseif($user->status === 'blocked')
                        <form action="{{ route('admin.users.unblock', $user) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-700 rounded-md hover:bg-emerald-200 font-medium text-xs">
                                Unblock
                            </button>
                        </form>
                    @endif
                    
                    <!-- Delete -->
                    @if(!$user->isAdmin() && $user->id !== auth()->id())
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this user permanently?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-1 bg-pink-100 text-pink-700 rounded-md hover:bg-pink-200 font-medium text-xs">
                                Delete
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                    No users found.
                </td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>

<!-- Import Modal -->
<div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <h3 class="text-lg font-bold mb-4">Import Users from Excel</h3>
        <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Excel File</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Import
                </button>
                <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
