<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = Str::lower(trim((string) config('auth.admin.email')));
        $password = (string) config('auth.admin.password');

        User::factory()->create([
            'name' => 'Librarium Administrator',
            'email' => $email !== '' ? $email : 'test@example.com',
            'password' => $password !== '' ? $password : 'password',
        ]);
    }
}
