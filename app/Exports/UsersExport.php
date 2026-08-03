<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Dùng CHUNG `User::scopeFilter` với màn `/admin/users`.
     *
     * Bản cũ chép lại logic lọc nhưng chỉ hiểu search/role/status, nên lọc
     * "sắp hết hạn" trên màn rồi bấm Export sẽ ra file chứa toàn bộ người dùng —
     * sai âm thầm. Giờ thêm bộ lọc mới là cả hai chỗ có ngay.
     */
    public function collection()
    {
        return User::filter($this->filters)->orderBy('id')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Role',
            'Nguồn tài khoản',
            'Status',
            'Ngày hết hạn',
            'Max Devices',
            'Violation Count',
            'Created At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            ucfirst($user->role),
            $user->sourceLabel(),
            ucfirst($user->status),
            $user->expires_at?->format('Y-m-d') ?? 'Không giới hạn',
            $user->max_devices,
            $user->violation_count,
            $user->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
