<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            if (! Schema::hasColumn('hearings', 'defendant_registries')) {
                $table->json('defendant_registries')->nullable()->after('defendant_names');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            if (Schema::hasColumn('hearings', 'defendant_registries')) {
                $table->dropColumn('defendant_registries');
            }
        });
    }
};
