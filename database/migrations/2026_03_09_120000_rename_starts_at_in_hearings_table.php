<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            if (Schema::hasColumn('hearings', 'starts_at') && ! Schema::hasColumn('hearings', 'start_at')) {
                $table->renameColumn('starts_at', 'start_at');
            }
            if (Schema::hasColumn('hearings', 'ends_at') && ! Schema::hasColumn('hearings', 'end_at')) {
                $table->renameColumn('ends_at', 'end_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            if (Schema::hasColumn('hearings', 'start_at') && ! Schema::hasColumn('hearings', 'starts_at')) {
                $table->renameColumn('start_at', 'starts_at');
            }
            if (Schema::hasColumn('hearings', 'end_at') && ! Schema::hasColumn('hearings', 'ends_at')) {
                $table->renameColumn('end_at', 'ends_at');
            }
        });
    }
};

