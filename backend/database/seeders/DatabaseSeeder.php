<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@aptis.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'max_devices' => 2,
            'violation_count' => 0,
        ]);

        // Seed quizzes and sets
        $this->call([
            QuizSeeder::class,
            SetSeeder::class,
        ]);

        // Create test user account
        User::create([
            'name' => 'Test User',
            'email' => 'user@aptis.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'status' => 'active',
            'max_devices' => 2,
            'violation_count' => 0,
        ]);
    }
}
