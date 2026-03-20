<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            // Хуучин буруу нэртэй баганууд байвал устгана (кодод ашиглагддаггүй).
            if (Schema::hasColumn('hearings', 'starts_at')) {
                $table->dropColumn('starts_at');
            }
            if (Schema::hasColumn('hearings', 'ends_at')) {
                $table->dropColumn('ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            // Down-д nullable багана болгон буцаана (шаардлагатай бол).
            if (! Schema::hasColumn('hearings', 'starts_at')) {
                $table->dateTime('starts_at')->nullable()->after('minute');
            }
            if (! Schema::hasColumn('hearings', 'ends_at')) {
                $table->dateTime('ends_at')->nullable()->after('starts_at');
            }
        });
    }
};

