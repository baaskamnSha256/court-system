<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            if (!Schema::hasColumn('hearings', 'matter_category_ids')) {
                $table->json('matter_category_ids')->nullable()->after('matter_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn('matter_category_ids');
        });
    }
};
