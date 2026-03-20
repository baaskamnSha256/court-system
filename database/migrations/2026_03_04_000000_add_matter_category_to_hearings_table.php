<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            if (! Schema::hasColumn('hearings', 'matter_category')) {
                $table->string('matter_category', 255)->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn('matter_category');
        });
    }
};
