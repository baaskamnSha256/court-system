<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            if (! Schema::hasColumn('hearings', 'session_openness')) {
                $table->string('session_openness', 50)->nullable()->after('hearing_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn('session_openness');
        });
    }
};
