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
    @php
        $khoaLoc = ['search', 'role', 'status', 'source', 'account_type', 'joined', 'joined_days', 'expiration', 'expire_days'];
        $dangLoc = request()->hasAny($khoaLoc);
    @endphp

    <form method="GET" class="mb-6">
        {{-- Ô tìm kiếm để riêng một hàng: nó là thứ được dùng nhiều nhất, nhét
             chung hàng với 6 ô select thì bị bóp còn một mẩu. --}}
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <input
                type="text"
                name="search"
                placeholder="Tìm theo tên hoặc email…"
                value="{{ request('search') }}"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 whitespace-nowrap">
                Lọc
            </button>
            @if($dangLoc)
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap text-center">
                    Xoá lọc
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
            {{-- Nguồn tài khoản: tự mua qua web vs admin tạo tay. Kèm số lượng
                 ngay trên nhãn để khỏi phải bấm từng cái mới biết nhóm nào bao nhiêu. --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nguồn tài khoản</label>
                <x-select name="source">
                    <option value="">Tất cả nguồn</option>
                    @foreach(\App\Models\User::SOURCE_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ request('source') === $key ? 'selected' : '' }}>
                            {{ $label }}@if(isset($demNguon[$key])) ({{ $demNguon[$key] }})@endif
                        </option>
                    @endforeach
                </x-select>
            </div>

            {{-- Mới thêm trong N ngày — theo NGÀY TẠO tài khoản. --}}
            <div x-data="{ moi: '{{ request('joined') }}' }">
                <label class="block text-xs font-medium text-gray-600 mb-1">Mới thêm trong</label>
                <div class="flex gap-2">
                    <x-select name="joined" x-model="moi">
                        <option value="">Mọi lúc</option>
                        <option value="7" {{ request('joined') === '7' ? 'selected' : '' }}>7 ngày qua</option>
                        <option value="14" {{ request('joined') === '14' ? 'selected' : '' }}>14 ngày qua</option>
                        <option value="30" {{ request('joined') === '30' ? 'selected' : '' }}>30 ngày qua</option>
                        <option value="custom" {{ request('joined') === 'custom' ? 'selected' : '' }}>Tuỳ chỉnh…</option>
                    </x-select>
                    <input
                        x-show="moi === 'custom'"
                        type="number" name="joined_days" min="1"
                        value="{{ request('joined_days') }}" placeholder="ngày"
                        class="w-20 px-2 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        x-cloak>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Ngày thi</label>
                <div x-data="{ exp: '{{ request('expiration') }}' }" class="flex gap-2">
                    <x-select name="expiration" x-model="exp">
                        {{-- Số đếm hiện thẳng trong lựa chọn (cùng kiểu với ô
                             "Nguồn"): nhìn phát biết ngay có bao nhiêu tài khoản
                             quá hạn lâu, không phải bấm từng bộ lọc mới biết. --}}
                        <option value="">Tất cả</option>
                        <option value="expired" {{ request('expiration') === 'expired' ? 'selected' : '' }}>Đã quá hạn ({{ $demHan->expired ?? 0 }})</option>
                        <option value="expired_30" {{ request('expiration') === 'expired_30' ? 'selected' : '' }}>Quá hạn trên 30 ngày ({{ $demHan->expired_30 ?? 0 }})</option>
                        <option value="expired_90" {{ request('expiration') === 'expired_90' ? 'selected' : '' }}>Quá hạn trên 90 ngày ({{ $demHan->expired_90 ?? 0 }})</option>
                        <option value="warning" {{ request('expiration') === 'warning' ? 'selected' : '' }}>Sắp thi (7 ngày) ({{ $demHan->warning ?? 0 }})</option>
                        <option value="custom" {{ request('expiration') === 'custom' ? 'selected' : '' }}>Sắp thi (tuỳ chỉnh)</option>
                        <option value="active" {{ request('expiration') === 'active' ? 'selected' : '' }}>Chưa thi ({{ $demHan->active ?? 0 }})</option>
                        <option value="never" {{ request('expiration') === 'never' ? 'selected' : '' }}>Không giới hạn ({{ $demHan->never ?? 0 }})</option>
                    </x-select>
                    <input
                        x-show="exp === 'custom'"
                        type="number" name="expire_days" min="1"
                        value="{{ request('expire_days') }}" placeholder="ngày"
                        class="w-20 px-2 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        x-cloak>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Trạng thái</label>
                <x-select name="status">
                    <option value="">Tất cả</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                    <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Bị khoá</option>
                </x-select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Vai trò</label>
                    <x-select name="role">
                        <option value="">Tất cả</option>
                        <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Học viên</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </x-select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Loại</label>
                    <x-select name="account_type">
                        <option value="">Tất cả</option>
                        <option value="unlimited" {{ request('account_type') === 'unlimited' ? 'selected' : '' }}>Vô hạn</option>
                        <option value="limited" {{ request('account_type') === 'limited' ? 'selected' : '' }}>Có thời hạn</option>
                    </x-select>
                </div>
            </div>
        </div>

        @if($dangLoc)
            <p class="mt-3 text-sm text-gray-600">
                Đang lọc — <strong>{{ $users->total() }}</strong> tài khoản khớp.
                Nút <strong>Export Excel</strong> bên dưới xuất đúng {{ $users->total() }} tài khoản này.
            </p>
        @endif
    </form>

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
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">DevTools</th>
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
                <td class="px-6 py-4 text-sm">
                    {{ $user->name }}
                    @unless($user->isAdmin())
                        {{-- Nguồn + ngày tạo ngay dưới tên: đây đúng là hai thứ hai
                             bộ lọc mới dùng, để cạnh nhau thì lọc xong nhìn phát biết
                             ngay có đúng nhóm mình cần không. --}}
                        <span class="block mt-0.5 text-xs text-gray-500">
                            @php
                                $bienThe = match($user->source) {
                                    \App\Models\User::SOURCE_PURCHASE => 'success',
                                    \App\Models\User::SOURCE_MANUAL   => 'warning',
                                    default                           => 'default',
                                };
                            @endphp
                            <x-badge :variant="$bienThe">{{ $user->sourceLabel() }}</x-badge>
                            <span class="ml-1">{{ $user->created_at->format('d/m/Y') }}</span>
                        </span>
                    @endunless
                </td>
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
                    <span class="{{ $user->violation_count >= $user->max_devices ? 'text-red-600 font-bold' : '' }}">
                        {{ $user->violation_count }}/{{ $user->max_devices }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    @if($user->isAdmin())
                        <span class="text-xs text-gray-400">Miễn trừ (admin)</span>
                    @else
                        <div class="flex items-center gap-2">
                            {{-- Số lần bị phát hiện mở DevTools --}}
                            @if(($user->devtools_flag_count ?? 0) > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-bold"
                                      title="Số lần bị phát hiện mở DevTools">
                                    ⚠ {{ $user->devtools_flag_count }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif

                            {{-- Trạng thái guard + nút bật/tắt --}}
                            <form action="{{ route('admin.users.toggle-devtools-guard', $user) }}" method="POST" class="inline-block"
                                  onsubmit="return confirm('{{ $user->devtools_guard_disabled ? 'Bật lại' : 'Tắt' }} chặn DevTools cho {{ $user->email }}?')">
                                @csrf
                                @if($user->devtools_guard_disabled)
                                    <button type="submit"
                                            class="px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-xs font-medium hover:bg-gray-300 transition-colors"
                                            title="Đang tắt — bấm để bật lại chặn DevTools">Tắt</button>
                                @else
                                    <button type="submit"
                                            class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-medium hover:bg-green-200 transition-colors"
                                            title="Đang bật — bấm để miễn trừ tài khoản này">Bật</button>
                                @endif
                            </form>
                        </div>
                    @endif
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
                                    Reset Writing
                                </button>
                            </form>
                            <!-- Reset Speaking AI Button -->
                            <form action="{{ route('admin.users.reset-speaking-ai', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Reset lượt chấm Speaking cho người dùng này?')" title="Reset lượt chấm Speaking">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-2 py-0.5 bg-teal-100 text-teal-700 rounded hover:bg-teal-200 font-medium text-xs">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                                    Reset Speaking
                                </button>
                            </form>
                            <form action="{{ route('admin.users.reset-all-ai', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Reset toàn bộ lượt chấm AI (Writing & Speaking) cho người dùng này?')" title="Reset toàn bộ lượt AI">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 font-medium text-xs">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path></svg>
                                    Reset All AI
                                </button>
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
                <td colspan="11" class="px-6 py-8 text-center text-gray-500">
                    No users found.
                </td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>

<!-- Import Modal -->
<div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto border w-[400px] shadow-lg rounded-xl bg-white">
        <div class="p-5">
            <h3 class="text-xl font-bold mb-2 text-gray-900">Nhập Danh Sách Người Dùng</h3>
            <p class="text-sm text-gray-500 mb-5">
                Vui lòng tải <a href="{{ route('admin.users.template') }}" class="text-blue-600 hover:underline font-medium">file mẫu (Template)</a> về máy, điền dữ liệu theo đúng định dạng các cột và tải lên.
            </p>

            <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-5 bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Chọn file Excel (.xlsx, .xls, .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required 
                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" 
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">
                        Hủy bỏ
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium shadow-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        Tải Lên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
        </form>
    </div>
</div>
@endsection
