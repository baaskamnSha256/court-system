<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hearing_id')->nullable()->index();
            $table->string('action', 32)->nullable()->index();
            $table->string('recipient_role', 64)->nullable()->index();
            $table->string('recipient_name')->nullable();
            $table->string('regnum', 32)->nullable()->index();
            $table->string('civil_id', 32)->nullable()->index();
            $table->string('title');
            $table->string('delivery_status', 32)->default('unknown')->index();
            $table->boolean('delivered')->default(false)->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->integer('api_status')->nullable();
            $table->string('api_message')->nullable();
            $table->string('request_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->json('context')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
