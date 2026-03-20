<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hearings', function (Blueprint $table) {
            $table->id();

            $table->string('case_no')->nullable();   // Хэргийн №
            $table->string('title');                 // Гарчиг
            $table->dateTime('starts_at');           // Хурал эхлэх цаг
            $table->string('courtroom')->nullable(); // Танхим

            $table->foreignId('judge_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('secretary_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('scheduled'); // scheduled|done|canceled
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hearings');
    }
};
