<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@court.mn'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
            ]
        );
        $judge = User::firstOrCreate(
            ['email' => 'judge@court.mn'],
            [
                'name' => 'Judge',
                'password' => Hash::make('password'),
            ]
        );
         $secretary = User::firstOrCreate(
            ['email' => 'secretary@court.mn'],
            [
                'name' => 'Secretary',
                'password' => Hash::make('password'),
            ]
        );
        $prosecutor = User::firstOrCreate(
            ['email' => 'prosecutor@court.mn'],
            ['name' => 'Prosecutor', 'password' => Hash::make('password')]
        );

        $courtClerk = User::firstOrCreate(
            ['email' => 'clerk@court.mn'],
            ['name' => 'Court Clerk', 'password' => Hash::make('password')]
        );

        $infoDesk = User::firstOrCreate(
            ['email' => 'info@court.mn'],
            ['name' => 'Info Desk', 'password' => Hash::make('password')]
        );

        $admin->assignRole('admin');
        $judge->assignRole('judge');
        $secretary->assignRole('secretary');
        $prosecutor->assignRole('prosecutor');
        $courtClerk->assignRole('court_clerk');
        $infoDesk->assignRole('info_desk');
    }
}