<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            // 1) Хурлын тайлбар төрөл (optional)
            $table->string('hearing_type')->nullable()->after('title'); 
            // 2) Шүүгдэгчид (олон) - гараас
            $table->json('defendant_names')->nullable()->after('hearing_type');

            // 4-6) өмгөөлөгчид (олон) - гараас
            $table->json('defendant_lawyers_text')->nullable()->after('defendant_names');
            $table->json('victim_lawyers_text')->nullable()->after('defendant_lawyers_text');
            $table->json('victim_legal_rep_lawyers_text')->nullable()->after('victim_lawyers_text');

            // 3) прокурор - системийн хэрэглэгчээс сонгоно (nullable)
           
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
           
            $table->dropColumn([
                'hearing_type',
                'defendant_names',
                'defendant_lawyers_text',
                'victim_lawyers_text',
                'victim_legal_rep_lawyers_text',
                
            ]);
        });
    }
};