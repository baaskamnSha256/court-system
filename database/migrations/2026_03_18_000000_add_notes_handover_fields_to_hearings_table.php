<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->unsignedBigInteger('clerk_id')->nullable()->after('secretary_id');
            $table->text('notes_handover_text')->nullable()->after('note'); // 12. Хурал хойшилсон тойм / нэмэлт тэмдэглэлүүд
            $table->string('notes_decided_matter', 255)->nullable()->after('notes_handover_text'); // 13. Шийдвэрлэсэн зүйл анги
            $table->string('notes_fine_units', 100)->nullable()->after('notes_decided_matter'); // 14. Торгох нэгж
            $table->string('notes_damage_amount', 100)->nullable()->after('notes_fine_units'); // 16. Хохирлын дүн
            $table->string('notes_decision_status', 100)->nullable()->after('notes_damage_amount'); // 17. ШХ шийдвэр (сонголт)

            $table->boolean('notes_handover_issued')->default(false)->after('notes_decision_status'); // 20. Тэмдэглэл гаргасан эсэх
            $table->timestamp('notes_handover_issued_at')->nullable()->after('notes_handover_issued'); // 19. ШХНБ дарга сонгосон цаг
            $table->timestamp('notes_handover_saved_at')->nullable()->after('notes_handover_issued_at'); // 21. Бүртгэсэн цаг

            $table->foreign('clerk_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->dropForeign(['clerk_id']);
            $table->dropColumn([
                'clerk_id',
                'notes_handover_text',
                'notes_decided_matter',
                'notes_fine_units',
                'notes_damage_amount',
                'notes_decision_status',
                'notes_handover_issued',
                'notes_handover_issued_at',
                'notes_handover_saved_at',
            ]);
        });
    }
};

