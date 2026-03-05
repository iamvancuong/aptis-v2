<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class UsersImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $defaultMaxDevices;

    public function __construct()
    {
        // Read once, not per row
        $this->defaultMaxDevices = (int)(Setting::where('key', 'default_max_devices')->value('value') ?? 2);
    }

    public function collection(Collection $rows)
    {
        // Extend execution time for large imports
        set_time_limit(0);

        $existingEmails = User::whereIn('email', $rows->pluck('email')->filter()->toArray())
            ->pluck('email')
            ->flip();

        $now = now()->toDateTimeString();
        $batch = [];

        foreach ($rows as $row) {
            $email = trim($row['email'] ?? '');
            if (empty($email) || isset($existingEmails[$email])) {
                continue; // skip empty or duplicate emails
            }

            $expiresAt = null;
            if (!empty($row['expires_days']) && is_numeric($row['expires_days'])) {
                $expiresAt = now()->addDays((int) $row['expires_days'])->toDateTimeString();
            }

            $batch[] = [
                'name'            => $row['name'] ?? '',
                'email'           => $email,
                'password'        => Hash::make($row['password'] ?? 'password123'),
                'role'            => $row['role'] ?? 'user',
                'status'          => 'active',
                'max_devices'     => isset($row['max_devices']) && is_numeric($row['max_devices'])
                                        ? (int) $row['max_devices']
                                        : $this->defaultMaxDevices,
                'target_level'    => $row['target_level'] ?? null,
                'expires_at'      => $expiresAt,
                'violation_count' => 0,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        // Batch insert (much faster than individual creates)
        foreach (array_chunk($batch, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
