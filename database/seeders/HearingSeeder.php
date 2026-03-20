<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hearing;
use App\Models\User;
use Carbon\Carbon;

class HearingSeeder extends Seeder
{
    public function run(): void
    {
        $judge = User::where('email','judge@court.mn')->first();
        $sec   = User::where('email','secretary@court.mn')->first();

        Hearing::create([
            'case_no' => '2026/01-001',
            'title' => 'Хурлын зар (Жишээ 1)',
            'starts_at' => Carbon::today()->setTime(10, 0),
            'courtroom' => 'Танхим 1',
            'judge_id' => $judge?->id,
            'secretary_id' => $sec?->id,
            'status' => 'scheduled',
        ]);

        Hearing::create([
            'case_no' => '2026/01-002',
            'title' => 'Хурлын зар (Жишээ 2)',
            'starts_at' => Carbon::today()->setTime(14, 30),
            'courtroom' => 'Танхим 2',
            'judge_id' => $judge?->id,
            'secretary_id' => $sec?->id,
            'status' => 'scheduled',
        ]);
    }
}
