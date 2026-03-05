<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'zalo_contact_number'],
            [
                'value' => '0989206132',
                'label' => 'Số Zalo đăng ký tài khoản (Admin)'
            ]
        );
    }
}
