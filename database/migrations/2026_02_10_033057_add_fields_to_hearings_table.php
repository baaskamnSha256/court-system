<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->foreignId('prosecutor_id')->nullable()->after('secretary_id')->constrained('users')->nullOnDelete();
            $table->string('lawyer_name')->nullable()->after('prosecutor_id');
            $table->string('defendant_name')->nullable()->after('lawyer_name');
            $table->string('victim_name')->nullable()->after('defendant_name');
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prosecutor_id');
            $table->dropColumn(['lawyer_name','defendant_name','victim_name']);
        });
    }
};
