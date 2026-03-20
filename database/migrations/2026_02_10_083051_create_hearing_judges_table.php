<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hearing_judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hearing_id')->constrained('hearings')->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(1); // 1=даргалагч
            $table->timestamps();

            $table->unique(['hearing_id','judge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hearing_judges');
    }
};
