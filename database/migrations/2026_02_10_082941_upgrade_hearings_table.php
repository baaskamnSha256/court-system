<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {

            // бүртгэсэн хэрэглэгч
            if (! Schema::hasColumn('hearings', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }

            // огноо + цаг/минут
            if (! Schema::hasColumn('hearings', 'hearing_date')) {
                $table->date('hearing_date')->nullable()->after('created_by');
            }
            if (! Schema::hasColumn('hearings', 'hour')) {
                $table->unsignedTinyInteger('hour')->nullable()->after('hearing_date');
            }
            if (! Schema::hasColumn('hearings', 'minute')) {
                $table->unsignedTinyInteger('minute')->nullable()->after('hour');
            }

            // start/end + duration
            if (! Schema::hasColumn('hearings', 'start_at')) {
                $table->dateTime('start_at')->nullable()->after('minute');
            }
            if (! Schema::hasColumn('hearings', 'end_at')) {
                $table->dateTime('end_at')->nullable()->after('start_at');
            }
            if (! Schema::hasColumn('hearings', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->default(30)->after('end_at');
            }

            // ⚠️ courtroom чинь өмнө нь add_fields_to_hearings_table дээр байгаа тул энд дахин нэмэхгүй
            // if (! Schema::hasColumn('hearings', 'courtroom')) { ... }  <-- БҮР АВААД ХАЯ

            // улсын яллагч
            if (! Schema::hasColumn('hearings', 'prosecutor_name')) {
                $table->string('prosecutor_name')->nullable()->after('courtroom');
            }

            // таслан сэргийлэх арга хэмжээ
            if (! Schema::hasColumn('hearings', 'preventive_measure')) {
                $table->string('preventive_measure')->nullable()->after('prosecutor_name');
            }

            // өмгөөлөгчид (multi) — JSON
            if (! Schema::hasColumn('hearings', 'defendant_lawyers')) {
                $table->json('defendant_lawyers')->nullable()->after('preventive_measure');
            }
            if (! Schema::hasColumn('hearings', 'victim_lawyers')) {
                $table->json('victim_lawyers')->nullable()->after('defendant_lawyers');
            }
            if (! Schema::hasColumn('hearings', 'victim_legal_rep_lawyers')) {
                $table->json('victim_legal_rep_lawyers')->nullable()->after('victim_lawyers');
            }
            if (! Schema::hasColumn('hearings', 'civil_plaintiff_lawyers')) {
                $table->json('civil_plaintiff_lawyers')->nullable()->after('victim_legal_rep_lawyers');
            }
            if (! Schema::hasColumn('hearings', 'civil_defendant_lawyers')) {
                $table->json('civil_defendant_lawyers')->nullable()->after('civil_plaintiff_lawyers');
            }

            // нэрс гараас (text)
            if (! Schema::hasColumn('hearings', 'defendants')) {
                $table->text('defendants')->nullable()->after('civil_defendant_lawyers');
            }
            if (! Schema::hasColumn('hearings', 'victim_name')) {
                $table->string('victim_name')->nullable()->after('defendants');
            }
            if (! Schema::hasColumn('hearings', 'witnesses')) {
                $table->text('witnesses')->nullable()->after('victim_name');
            }
            if (! Schema::hasColumn('hearings', 'experts')) {
                $table->text('experts')->nullable()->after('witnesses');
            }
            if (! Schema::hasColumn('hearings', 'victim_legal_rep')) {
                $table->string('victim_legal_rep')->nullable()->after('experts');
            }
            if (! Schema::hasColumn('hearings', 'civil_plaintiff')) {
                $table->string('civil_plaintiff')->nullable()->after('victim_legal_rep');
            }
            if (! Schema::hasColumn('hearings', 'civil_defendant')) {
                $table->string('civil_defendant')->nullable()->after('civil_plaintiff');
            }

            // 7) шүүгчдийн нэр (гараас)
            if (! Schema::hasColumn('hearings', 'judge_names_text')) {
                $table->string('judge_names_text', 1000)->nullable()->after('civil_defendant');
            }
        });
    }

    public function down(): void
    {
        // хөгжүүлэлтийн үед ихэвчлэн down хэрэггүй.
    }
};
