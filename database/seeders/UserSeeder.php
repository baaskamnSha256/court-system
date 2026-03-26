<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Анхны тест хэрэглэгчид. Дахин ажиллуулахад нууц үгийг дахин "password" болгоно.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $plain = 'password';

        $users = [
            ['email' => 'admin@court.mn', 'name' => 'System Admin', 'role' => 'admin'],
            ['email' => 'judge@court.mn', 'name' => 'Judge', 'role' => 'judge'],
            ['email' => 'secretary@court.mn', 'name' => 'Secretary', 'role' => 'secretary'],
            ['email' => 'prosecutor@court.mn', 'name' => 'Prosecutor', 'role' => 'prosecutor'],
            ['email' => 'clerk@court.mn', 'name' => 'Court Clerk', 'role' => 'court_clerk'],
            ['email' => 'info@court.mn', 'name' => 'Info Desk', 'role' => 'info_desk'],
        ];

        foreach ($users as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make($plain),
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$row['role']]);
        }
    }
}
