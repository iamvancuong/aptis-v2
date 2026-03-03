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
            <x-select name="role">
                <option value="">All Roles</option>
                <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </x-select>
            <x-select name="status">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
            </x-select>
            <div x-data="{ exp: '{{ request('expiration') }}' }" class="flex items-center gap-2">
                <x-select name="expiration" x-model="exp">
                    <option value="">Lọc ngày thi</option>
                    <option value="expired">Đã quá hạn</option>
                    <option value="warning">Sắp thi (7 ngày)</option>
                    <option value="custom">Sắp thi (Tùy chỉnh ngày)</option>
                    <option value="active">Chưa thi</option>
                    <option value="never">Không giới hạn</option>
                </x-select>
                <input 
                    x-show="exp === 'custom'"
                    type="number" 
                    name="expire_days" 
                    value="{{ request('expire_days') }}"
                    placeholder="Số ngày..." 
                    class="w-28 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    min="1"
                    x-cloak
                >
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 whitespace-nowrap">
                Filter
            </button>
            @if(request()->hasAny(['search', 'role', 'status', 'expiration', 'expire_days']))
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 whitespace-nowrap">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <div class="flex flex-wrap gap-3">
        <button id="bulk-delete-btn" style="display: none;" onclick="bulkDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm shadow-sm transition-all items-center">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            Xoá đã chọn (<span class="count">0</span>)
        </button>
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
            <th class="px-6 py-3 w-10 text-left text-xs font-medium text-gray-500 uppercase">
                <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Violations</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày thi</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quick Extend</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($users as $user)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if(!$user->isAdmin() && $user->id !== auth()->id())
                        <input type="checkbox" value="{{ $user->id }}" class="bulk-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @endif
                </td>
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
                        @if($status === 'expired') Đã quá hạn
                        @elseif($status === 'warning') {{ $user->expires_at->format('d/m/Y') }} (Còn {{ $user->daysUntilExpiration() }} ngày)
                        @elseif($status === 'active') {{ $user->expires_at->format('d/m/Y') }}
                        @else Không giới hạn
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
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Xem
                    </a>
                    
                    <!-- Edit -->
                    <a href="{{ route('admin.users.edit', $user) }}" 
                        class="inline-flex items-center px-3 py-1 bg-violet-100 text-violet-700 rounded-md hover:bg-violet-200 font-medium text-xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Sửa
                    </a>
                    
                    <!-- Reset AI & Add AI -->
                    @if(!$user->isAdmin())
                        <div class="inline-flex gap-1 items-center bg-gray-50 p-1 rounded-md border border-gray-200 h-8">
                            <form action="{{ route('admin.users.reset-ai', $user) }}" method="POST" class="inline-block" title="Reset lượt chấm Writing">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-2 py-0.5 bg-fuchsia-100 text-fuchsia-700 rounded hover:bg-fuchsia-200 font-medium text-xs">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    RS-WR
                                </button>
                            </form>
                            <!-- Reset Speaking AI Button -->
                            <form action="{{ route('admin.users.reset-speaking-ai', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Reset lượt chấm Speaking cho người dùng này?')" title="Reset lượt chấm Speaking">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-2 py-0.5 bg-teal-100 text-teal-700 rounded hover:bg-teal-200 font-medium text-xs">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                                    RS-SP
                                </button>
                            </form>
                            <span class="text-gray-300 mx-0.5 text-xs">|</span>
                            <form action="{{ route('admin.users.add-ai', $user) }}" method="POST" class="inline-flex items-center gap-1">
                                    @csrf
                                    <input type="number" name="amount" value="10" min="1" max="100" class="w-12 h-5 text-xs px-1 border-gray-300 rounded" required title="Số lượt muốn cộng thêm (mặc định gốc là 10, đang cộng thêm {{ $user->ai_extra_uses }})">
                                    <button type="submit" class="inline-flex items-center px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 font-medium text-xs" title="Thêm lượt AI">+</button>
                            </form>
                        </div>
                    @endif
                    
                    <!-- Block/Unblock -->
                    @if($user->status === 'active' && !$user->isAdmin())
                        <form action="{{ route('admin.users.block', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Khóa người dùng này?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 font-medium text-xs">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Khóa
                            </button>
                        </form>
                    @elseif($user->status === 'blocked')
                        <form action="{{ route('admin.users.unblock', $user) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-700 rounded-md hover:bg-emerald-200 font-medium text-xs">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                Mở khóa
                            </button>
                        </form>
                    @endif
                    
                    <!-- Delete -->
                    @if(!$user->isAdmin() && $user->id !== auth()->id())
                        <form id="delete-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Xoá vĩnh viễn người dùng này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-1 bg-pink-100 text-pink-700 rounded-md hover:bg-pink-200 font-medium text-xs">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Xóa
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="px-6 py-8 text-center text-gray-500">
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
