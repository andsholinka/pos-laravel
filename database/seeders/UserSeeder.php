<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts (password: "password"). Ids: 1 admin, 2 kasir, 3 kasir2, 4 dewi (inactive).
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Andi Pratama', 'email' => 'admin@pos.test', 'role' => Role::Admin, 'is_active' => true],
            ['name' => 'Siti Rahmawati', 'email' => 'kasir@pos.test', 'role' => Role::Kasir, 'is_active' => true],
            ['name' => 'Budi Santoso', 'email' => 'kasir2@pos.test', 'role' => Role::Kasir, 'is_active' => true],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@pos.test', 'role' => Role::Kasir, 'is_active' => false],
        ];

        foreach ($users as $data) {
            (new User)->forceFill($data + [
                'password' => 'password', // hashed by the model cast
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
