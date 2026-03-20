<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            if (! Schema::hasColumn('hearings', 'hearing_state')) {
                $table->string('hearing_state', 100)->default('Хэвийн')->after('hearing_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn('hearing_state');
        });
    }
};
