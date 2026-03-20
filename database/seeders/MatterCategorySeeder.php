<?php

namespace Database\Seeders;

use App\Models\MatterCategory;
use Illuminate\Database\Seeder;

class MatterCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'Эрүүгийн хэрэг',
            'Эрүүгийн хариуцлага',
            'Урьдчилсан хэлэлцүүлэг',
            'Иргэний хэрэг',
            'Захиргааны хэрэг',
            'Хөдөлмөрийн хэрэг',
            'Татан буулгах хэрэг',
            'Бусад',
        ];

        foreach ($defaults as $i => $name) {
            MatterCategory::firstOrCreate(
                ['name' => $name],
                ['sort_order' => $i + 1]
            );
        }
    }
}
