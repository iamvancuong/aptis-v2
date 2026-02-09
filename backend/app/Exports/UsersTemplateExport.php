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
            ['John Doe', 'john@example.com', 'password123', 'user', 2],
            ['Jane Smith', 'jane@example.com', 'password123', 'user', 2],
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Password',
            'Role (user/admin)',
            'Max Devices',
        ];
    }
}
