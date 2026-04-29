<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table): void {
            if (! Schema::hasColumn('hearings', 'victim_registries')) {
                $table->json('victim_registries')->nullable()->after('defendant_registries');
            }
            if (! Schema::hasColumn('hearings', 'victim_legal_rep_registries')) {
                $table->json('victim_legal_rep_registries')->nullable()->after('victim_registries');
            }
            if (! Schema::hasColumn('hearings', 'witness_registries')) {
                $table->json('witness_registries')->nullable()->after('victim_legal_rep_registries');
            }
            if (! Schema::hasColumn('hearings', 'civil_plaintiff_registries')) {
                $table->json('civil_plaintiff_registries')->nullable()->after('witness_registries');
            }
            if (! Schema::hasColumn('hearings', 'civil_defendant_registries')) {
                $table->json('civil_defendant_registries')->nullable()->after('civil_plaintiff_registries');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table): void {
            $drop = array_values(array_filter([
                Schema::hasColumn('hearings', 'victim_registries') ? 'victim_registries' : null,
                Schema::hasColumn('hearings', 'victim_legal_rep_registries') ? 'victim_legal_rep_registries' : null,
                Schema::hasColumn('hearings', 'witness_registries') ? 'witness_registries' : null,
                Schema::hasColumn('hearings', 'civil_plaintiff_registries') ? 'civil_plaintiff_registries' : null,
                Schema::hasColumn('hearings', 'civil_defendant_registries') ? 'civil_defendant_registries' : null,
            ]));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};

