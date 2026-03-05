<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Return empty array with just headings
        return [
            ['Nguyen Van A', 'vana@example.com', 'password123', 'user', 'B1', 30],
            ['Tran Anh B', 'anhb@example.com', '12345678', 'user', 'B2', ''],
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Password',
            'Role',
            'Target Level',
            'Expires Days',
        ];
    }
}
